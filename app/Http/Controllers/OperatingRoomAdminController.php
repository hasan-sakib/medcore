<?php

namespace App\Http\Controllers;

use App\Models\OperatingRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OperatingRoomAdminController extends Controller
{
    public const OR_TYPES = ['general', 'cardiac', 'ortho', 'neuro', 'emergency', 'obstetric'];

    public const OR_STATUSES = ['available', 'scheduled', 'in_use', 'maintenance'];

    public function index(): Response
    {
        $rooms = OperatingRoom::query()
            ->withCount([
                'schedules',
                'schedules as open_schedules_count' => fn ($q) => $q->whereIn('status', ['scheduled', 'in_progress']),
            ])
            ->orderBy('room_number')
            ->get();

        return Inertia::render('Facilities/OperatingRooms', [
            'rooms' => $rooms,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        OperatingRoom::create($data + ['status' => 'available', 'is_active' => true]);

        return back()->with('success', 'Operating room created.');
    }

    public function update(Request $request, OperatingRoom $operatingRoom): RedirectResponse
    {
        $data = $this->validated($request, $operatingRoom);

        if (array_key_exists('is_active', $data) && ! $data['is_active'] && $operatingRoom->is_active) {
            if ($this->hasOpenSchedules($operatingRoom)) {
                return back()->withErrors(['is_active' => 'Cannot deactivate an operating room with scheduled or in-progress procedures.']);
            }
        }

        $operatingRoom->update($data);

        return back()->with('success', 'Operating room updated.');
    }

    public function destroy(OperatingRoom $operatingRoom): RedirectResponse
    {
        if ($this->hasOpenSchedules($operatingRoom)) {
            return back()->with('error', 'Operating room has scheduled or in-progress procedures and cannot be deleted.');
        }

        if ($operatingRoom->schedules()->exists()) {
            return back()->with('error', 'Operating room has schedule history and cannot be deleted. Deactivate it instead.');
        }

        $operatingRoom->delete();

        return back()->with('success', 'Operating room deleted.');
    }

    private function hasOpenSchedules(OperatingRoom $room): bool
    {
        return $room->schedules()->whereIn('status', ['scheduled', 'in_progress'])->exists();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?OperatingRoom $room = null): array
    {
        $tenantId = $request->user()->tenant_id;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'room_number' => [
                'required', 'string', 'max:20',
                Rule::unique('operating_rooms', 'room_number')->where('tenant_id', $tenantId)->ignore($room?->id),
            ],
            'or_type' => ['required', Rule::in(self::OR_TYPES)],
            'status' => ['sometimes', Rule::in(self::OR_STATUSES)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
