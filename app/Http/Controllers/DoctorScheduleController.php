<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorScheduleController extends Controller
{
    public function index(): Response
    {
        $schedules = DoctorSchedule::with(['doctor', 'department'])
            ->orderBy('user_id')
            ->orderBy('day_of_week')
            ->paginate(50);

        return Inertia::render('DoctorSchedules/Index', [
            'schedules' => $schedules,
            'doctors' => User::role('doctor')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration' => ['required', 'integer', 'in:10,15,20,30,45,60'],
            'max_patients' => ['required', 'integer', 'min:1', 'max:100'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        DoctorSchedule::create($validated);

        return back()->with('success', 'Schedule created.');
    }

    public function update(Request $request, DoctorSchedule $doctorSchedule): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'day_of_week' => ['sometimes', 'integer', 'between:0,6'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'slot_duration' => ['sometimes', 'integer', 'in:10,15,20,30,45,60'],
            'max_patients' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date'],
        ]);

        $doctorSchedule->update($validated);

        return back()->with('success', 'Schedule updated.');
    }

    public function destroy(DoctorSchedule $doctorSchedule): RedirectResponse
    {
        $doctorSchedule->delete();

        return back()->with('success', 'Schedule removed.');
    }
}
