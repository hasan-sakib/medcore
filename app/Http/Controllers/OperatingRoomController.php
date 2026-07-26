<?php

namespace App\Http\Controllers;

use App\Events\OrScheduleUpdated;
use App\Models\OperatingRoom;
use App\Models\OrSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OperatingRoomController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OperatingRoom::class);

        $date = $request->date('date', now()->toDateString());

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
            'rooms'    => $rooms,
            'surgeons' => $surgeons,
            'date'     => $date->toDateString(),
        ]);
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $this->authorize('create', OrSchedule::class);

        $data = $request->validate([
            'operating_room_id' => ['required', 'exists:operating_rooms,id'],
            'surgeon_id'        => ['required', 'exists:users,id'],
            'encounter_id'      => ['nullable', 'exists:encounters,id'],
            'procedure_name'    => ['required', 'string', 'max:255'],
            'scheduled_start'   => ['required', 'date'],
            'scheduled_end'     => ['required', 'date', 'after:scheduled_start'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        $schedule = OrSchedule::create(array_merge($data, ['created_by' => $request->user()->id]));
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
