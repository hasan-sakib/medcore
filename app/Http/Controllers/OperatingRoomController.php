<?php

namespace App\Http\Controllers;

use App\Events\OrScheduleUpdated;
use App\Models\OperatingRoom;
use App\Models\OrSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OperatingRoomController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OperatingRoom::class);

        $date = $request->date('date') ?? now();

        $rooms = OperatingRoom::where('is_active', true)
            ->with([
                'schedules' => fn ($q) => $q
                    ->whereDate('scheduled_start', $date)
                    ->with(['surgeon', 'encounter.patient'])
                    ->orderBy('scheduled_start'),
            ])
            ->orderBy('room_number')
            ->get();

        $surgeons = User::where('tenant_id', auth()->user()->tenant_id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'doctor'))
            ->get(['id', 'name']);

        return Inertia::render('OperatingRooms/Index', [
            'rooms' => $rooms,
            'surgeons' => $surgeons,
            'date' => $date->toDateString(),
        ]);
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $this->authorize('create', OrSchedule::class);

        $tenantId = $request->user()->tenant_id;

        // `exists:` rules bypass the tenant scope, so constrain them explicitly.
        $data = $request->validate([
            'operating_room_id' => ['required', Rule::exists('operating_rooms', 'id')->where('tenant_id', $tenantId)],
            'surgeon_id' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'encounter_id' => ['nullable', Rule::exists('encounters', 'id')->where('tenant_id', $tenantId)],
            'procedure_name' => ['required', 'string', 'max:255'],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date', 'after:scheduled_start'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Serialise bookings per room: lock the room row, then check for overlaps so two
        // concurrent requests cannot both pass the check and double-book the theatre.
        $schedule = DB::transaction(function () use ($data, $request) {
            OperatingRoom::whereKey($data['operating_room_id'])->lockForUpdate()->firstOrFail();

            $overlapping = fn ($q) => $q->whereNotIn('status', ['cancelled', 'completed'])
                ->where('scheduled_start', '<', $data['scheduled_end'])
                ->where('scheduled_end', '>', $data['scheduled_start']);

            if (OrSchedule::where('operating_room_id', $data['operating_room_id'])->where($overlapping)->exists()) {
                throw ValidationException::withMessages([
                    'scheduled_start' => 'This operating room is already booked for an overlapping time.',
                ]);
            }

            if (OrSchedule::where('surgeon_id', $data['surgeon_id'])->where($overlapping)->exists()) {
                throw ValidationException::withMessages([
                    'surgeon_id' => 'This surgeon already has a procedure scheduled in that time window.',
                ]);
            }

            return OrSchedule::create(array_merge($data, ['created_by' => $request->user()->id]));
        });

        $schedule->load('surgeon');

        OrScheduleUpdated::dispatch($schedule);

        return back()->with('success', 'OR procedure scheduled.');
    }

    public function updateScheduleStatus(Request $request, OrSchedule $orSchedule): RedirectResponse
    {
        $this->authorize('update', $orSchedule);

        $data = $request->validate([
            'status' => ['required', 'in:scheduled,in_progress,completed,cancelled'],
        ]);

        $updates = ['status' => $data['status']];

        if ($data['status'] === 'in_progress' && ! $orSchedule->actual_start) {
            $updates['actual_start'] = now();
        }

        if ($data['status'] === 'completed' && ! $orSchedule->actual_end) {
            $updates['actual_end'] = now();
        }

        $orSchedule->update($updates);
        $orSchedule->load('surgeon');

        OrScheduleUpdated::dispatch($orSchedule->fresh());

        return back()->with('success', 'Schedule status updated.');
    }
}
