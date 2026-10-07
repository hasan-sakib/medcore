<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public const ROOM_TYPES = ['general', 'private', 'semi_private', 'icu', 'isolation'];

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Room::create($data + ['is_active' => $data['is_active'] ?? true]);

        return back()->with('success', 'Room created.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $data = $this->validated($request, $room);

        if ((int) $data['ward_id'] !== $room->ward_id && $room->beds()->exists()) {
            return back()->withErrors(['ward_id' => 'Cannot move a room that still has beds.']);
        }

        if (array_key_exists('is_active', $data) && ! $data['is_active'] && $room->is_active) {
            if ($room->beds()->where('status', 'occupied')->exists()) {
                return back()->withErrors(['is_active' => 'Cannot deactivate a room that has occupied beds.']);
            }
        }

        $room->update($data);

        return back()->with('success', 'Room updated.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->beds()->exists()) {
            return back()->with('error', 'Room still has beds. Remove or move them first, or deactivate the room instead.');
        }

        $room->delete();

        return back()->with('success', 'Room deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Room $room = null): array
    {
        $tenantId = $request->user()->tenant_id;

        return $request->validate([
            'ward_id' => ['required', 'integer', Rule::exists('wards', 'id')->where('tenant_id', $tenantId)],
            'room_number' => [
                'required', 'string', 'max:20',
                Rule::unique('rooms', 'room_number')->where('tenant_id', $tenantId)->ignore($room?->id),
            ],
            'room_type' => ['required', Rule::in(self::ROOM_TYPES)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
