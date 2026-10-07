<?php

namespace App\Services;

use App\Events\BedStatusChanged;
use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BedAllocationService
{
    /**
     * Admit a patient to a bed — race-safe via Redis lock + DB pessimistic lock.
     *
     * @throws \RuntimeException if the bed is not available
     */
    public function admit(Bed $bed, Patient $patient, ?Encounter $encounter, User $allocatedBy): BedAllocation
    {
        $lock = Cache::lock("bed:admit:{$bed->id}", 10);

        return $lock->block(5, function () use ($bed, $patient, $encounter, $allocatedBy): BedAllocation {
            return DB::transaction(function () use ($bed, $patient, $encounter, $allocatedBy): BedAllocation {
                $fresh = Bed::where('id', $bed->id)->lockForUpdate()->first();

                if ($fresh->status !== 'available') {
                    throw new \RuntimeException("Bed {$fresh->bed_number} is not available (status: {$fresh->status}).");
                }

                $allocation = BedAllocation::create([
                    'bed_id' => $fresh->id,
                    'patient_id' => $patient->id,
                    'encounter_id' => $encounter?->id,
                    'allocated_by' => $allocatedBy->id,
                    'admitted_at' => now(),
                ]);

                $fresh->update(['status' => 'occupied']);

                $allocation->load('patient');
                BedStatusChanged::dispatch($fresh->fresh(), $allocation);

                return $allocation;
            });
        });
    }

    /**
     * Discharge a patient — sets bed to cleaning, not available yet.
     */
    public function discharge(BedAllocation $allocation, string $reason, User $dischargedBy): void
    {
        $lock = Cache::lock("bed:admit:{$allocation->bed_id}", 10);

        $lock->block(5, function () use ($allocation, $reason): void {
            DB::transaction(function () use ($allocation, $reason): void {
                $bed = Bed::where('id', $allocation->bed_id)->lockForUpdate()->first();

                $allocation->update([
                    'discharged_at' => now(),
                    'discharge_reason' => $reason,
                ]);

                $bed->update(['status' => 'cleaning']);

                BedStatusChanged::dispatch($bed->fresh());
            });
        });
    }

    /**
     * Mark a bed as available after cleaning is done.
     */
    public function markAvailable(Bed $bed): void
    {
        DB::transaction(function () use ($bed): void {
            $fresh = Bed::where('id', $bed->id)->lockForUpdate()->first();
            $fresh->update(['status' => 'available']);
            BedStatusChanged::dispatch($fresh->fresh());
        });
    }

    /**
     * Toggle maintenance status on a bed that has no active allocation.
     */
    public function toggleMaintenance(Bed $bed): void
    {
        DB::transaction(function () use ($bed): void {
            $fresh = Bed::where('id', $bed->id)->lockForUpdate()->first();
            $newStatus = $fresh->status === 'maintenance' ? 'available' : 'maintenance';
            $fresh->update(['status' => $newStatus]);
            BedStatusChanged::dispatch($fresh->fresh());
        });
    }
}
