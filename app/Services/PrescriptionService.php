<?php

namespace App\Services;

use App\Models\DispenseRecord;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrescriptionService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Create a prescription with its items atomically.
     *
     * @param  array{patient_id: int, encounter_id?: int|null, notes?: string|null, items: array<int, array{medicine_id: int, dosage_instruction: string, frequency: string, quantity_prescribed: int, duration_days?: int|null, notes?: string|null}>}  $data
     */
    public function create(array $data): Prescription
    {
        return DB::transaction(function () use ($data): Prescription {
            $prescription = Prescription::create([
                'patient_id' => $data['patient_id'],
                'encounter_id' => $data['encounter_id'] ?? null,
                'prescribed_by' => Auth::id(),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medicine_id' => $item['medicine_id'],
                    'dosage_instruction' => $item['dosage_instruction'],
                    'frequency' => $item['frequency'],
                    'duration_days' => $item['duration_days'] ?? null,
                    'quantity_prescribed' => $item['quantity_prescribed'],
                    'quantity_dispensed' => 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $prescription->load('items');
        });
    }

    /**
     * Dispense a single prescription item (full quantity). Updates item + prescription status.
     *
     * @return Collection<int, DispenseRecord>
     */
    public function fillItem(PrescriptionItem $item, int $dispensedBy): Collection
    {
        $item->load('prescription');
        $prescription = $item->prescription;
        assert($prescription instanceof Prescription);

        if (in_array($prescription->status, ['filled', 'cancelled'], true)) {
            throw new \RuntimeException('Prescription is '.$prescription->status.' and cannot be dispensed.');
        }

        $remaining = $item->quantity_prescribed - $item->quantity_dispensed;

        if ($remaining <= 0) {
            throw new \RuntimeException('This prescription item has already been fully dispensed.');
        }

        $records = $this->inventory->deductStockFEFO(
            $item->medicine_id,
            $remaining,
            $dispensedBy,
            $prescription->id,
            $item->id
        );

        $item->increment('quantity_dispensed', $remaining);
        $this->recalcStatus($prescription->fresh());

        return $records;
    }

    /**
     * Cancel a pending prescription.
     */
    public function cancel(Prescription $prescription, string $reason): Prescription
    {
        if ($prescription->status !== 'pending') {
            throw new \RuntimeException('Only pending prescriptions can be cancelled.');
        }

        $prescription->update([
            'status' => 'cancelled',
            'notes' => trim(($prescription->notes ?? '').' Cancelled: '.$reason),
        ]);

        return $prescription->fresh();
    }

    private function recalcStatus(Prescription $prescription): void
    {
        $items = $prescription->items()->get();
        $totalPrescribed = $items->sum('quantity_prescribed');
        $totalDispensed = $items->sum('quantity_dispensed');

        if ($totalDispensed >= $totalPrescribed) {
            $status = 'filled';
        } elseif ($totalDispensed > 0) {
            $status = 'partially_filled';
        } else {
            $status = 'pending';
        }

        $prescription->update(['status' => $status]);
    }
}
