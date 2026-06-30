<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    /**
     * Check if a doctor has an available slot (schedule + no conflicts).
     */
    public function isSlotAvailable(int $doctorId, Carbon $scheduledAt, Carbon $endsAt): bool
    {
        $schedule = $this->findSchedule($doctorId, $scheduledAt);

        if (! $schedule) {
            return false;
        }

        if (! $this->withinScheduleWindow($schedule, $scheduledAt, $endsAt)) {
            return false;
        }

        return ! $this->hasConflict($doctorId, $scheduledAt, $endsAt);
    }

    /**
     * Book an appointment inside a transaction with pessimistic row locks.
     * Throws \RuntimeException if the slot is unavailable.
     *
     * @param  array{patient_id: int, doctor_id: int, department_id?: int|null, scheduled_at: Carbon|string, reason?: string|null}  $data
     */
    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data): Appointment {
            $scheduledAt = Carbon::parse($data['scheduled_at']);

            $schedule = DoctorSchedule::where('user_id', $data['doctor_id'])
                ->where('day_of_week', $scheduledAt->dayOfWeek)
                ->where('is_active', true)
                ->where(function ($q) use ($scheduledAt) {
                    $q->whereNull('effective_from')
                        ->orWhere('effective_from', '<=', $scheduledAt->toDateString());
                })
                ->where(function ($q) use ($scheduledAt) {
                    $q->whereNull('effective_until')
                        ->orWhere('effective_until', '>=', $scheduledAt->toDateString());
                })
                ->lockForUpdate()
                ->first();

            if (! $schedule) {
                throw new \RuntimeException('Doctor has no schedule on this day.');
            }

            $endsAt = $scheduledAt->copy()->addMinutes($schedule->slot_duration);

            if (! $this->withinScheduleWindow($schedule, $scheduledAt, $endsAt)) {
                throw new \RuntimeException('Requested time is outside the doctor\'s working hours.');
            }

            $conflict = Appointment::where('doctor_id', $data['doctor_id'])
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->where('scheduled_at', '<', $endsAt)
                ->where('ends_at', '>', $scheduledAt)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new \RuntimeException('This slot is no longer available.');
            }

            return Appointment::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'department_id' => $data['department_id'] ?? null,
                'scheduled_at' => $scheduledAt,
                'ends_at' => $endsAt,
                'status' => 'pending',
                'reason' => $data['reason'] ?? null,
            ]);
        });
    }

    /**
     * Cancel an appointment.
     */
    public function cancel(Appointment $appointment, int $cancelledBy, string $reason = ''): Appointment
    {
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $appointment->fresh();
    }

    /**
     * Return all open slots for a doctor on a given date.
     *
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function availableSlots(int $doctorId, Carbon $date): array
    {
        $schedule = $this->findSchedule($doctorId, $date);

        if (! $schedule) {
            return [];
        }

        $booked = Appointment::where('doctor_id', $doctorId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->whereDate('scheduled_at', $date->toDateString())
            ->get(['scheduled_at', 'ends_at']);

        $slots = [];
        $current = Carbon::parse($date->toDateString().' '.$schedule->start_time);
        $schedEnd = Carbon::parse($date->toDateString().' '.$schedule->end_time);
        $duration = $schedule->slot_duration;

        while ($current->copy()->addMinutes($duration)->lte($schedEnd)) {
            $slotEnd = $current->copy()->addMinutes($duration);
            $isBooked = $booked->first(
                fn ($b) => Carbon::parse($b->scheduled_at)->lt($slotEnd)
                    && Carbon::parse($b->ends_at)->gt($current)
            );

            if (! $isBooked) {
                $slots[] = ['start' => $current->copy(), 'end' => $slotEnd];
            }

            $current->addMinutes($duration);
        }

        return $slots;
    }

    private function findSchedule(int $doctorId, Carbon $date): ?DoctorSchedule
    {
        return DoctorSchedule::where('user_id', $doctorId)
            ->where('day_of_week', $date->dayOfWeek)
            ->where('is_active', true)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $date->toDateString());
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date->toDateString());
            })
            ->first();
    }

    private function withinScheduleWindow(DoctorSchedule $schedule, Carbon $scheduledAt, Carbon $endsAt): bool
    {
        $slotStart = $scheduledAt->format('H:i:s');
        $slotEnd = $endsAt->format('H:i:s');

        return $slotStart >= $schedule->start_time && $slotEnd <= $schedule->end_time;
    }

    private function hasConflict(int $doctorId, Carbon $scheduledAt, Carbon $endsAt): bool
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('scheduled_at', '<', $endsAt)
            ->where('ends_at', '>', $scheduledAt)
            ->exists();
    }
}
