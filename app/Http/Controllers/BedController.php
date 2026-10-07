<?php

namespace App\Http\Controllers;

use App\Events\BedStatusChanged;
use App\Models\Bed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BedController extends Controller
{
    public const BED_TYPES = ['standard', 'icu', 'isolation', 'pediatric', 'bariatric', 'electric'];

    /** Statuses an admin may set by hand. "occupied" is only ever set by admission. */
    public const MANUAL_STATUSES = ['available', 'maintenance', 'reserved', 'cleaning'];

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['status'] = $data['status'] ?? 'available';
        $data['is_active'] = $data['is_active'] ?? true;

        $bed = Bed::create($data);

        BedStatusChanged::dispatch($bed->fresh());

        return back()->with('success', "Bed {$bed->bed_number} created.");
    }

    public function update(Request $request, Bed $bed): RedirectResponse
    {
        $data = $this->validated($request, $bed);

        if ($this->isOccupied($bed)) {
            $changesLocation = $data['ward_id'] !== $bed->ward_id
                || $data['room_id'] !== $bed->room_id;
            $deactivates = array_key_exists('is_active', $data) && ! $data['is_active'];
            $changesStatus = isset($data['status']) && $data['status'] !== $bed->status;

            if ($changesLocation || $deactivates || $changesStatus) {
                return back()->with('error', "Bed {$bed->bed_number} is occupied; discharge the patient before moving, deactivating or changing its status.");
            }
        }

        $bed->update($data);

        if ($bed->wasChanged(['status', 'is_active', 'ward_id', 'room_id', 'bed_type', 'bed_number'])) {
            BedStatusChanged::dispatch($bed->fresh());
        }

        return back()->with('success', "Bed {$bed->bed_number} updated.");
    }

    public function destroy(Bed $bed): RedirectResponse
    {
        if ($this->isOccupied($bed)) {
            return back()->with('error', "Bed {$bed->bed_number} is occupied and cannot be deleted.");
        }

        if ($bed->allocations()->exists()) {
            return back()->with('error', "Bed {$bed->bed_number} has allocation history and cannot be deleted. Deactivate it instead.");
        }

        $bed->delete();

        return back()->with('success', 'Bed deleted.');
    }

    private function isOccupied(Bed $bed): bool
    {
        return $bed->status === 'occupied'
            || $bed->allocations()->whereNull('discharged_at')->exists();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Bed $bed = null): array
    {
        $tenantId = $request->user()->tenant_id;

        $data = $request->validate([
            'ward_id' => ['required', 'integer', Rule::exists('wards', 'id')->where('tenant_id', $tenantId)],
            'room_id' => [
                'nullable', 'integer',
                Rule::exists('rooms', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('ward_id', $request->input('ward_id')),
            ],
            'bed_number' => [
                'required', 'string', 'max:20',
                Rule::unique('beds', 'bed_number')->where('tenant_id', $tenantId)->ignore($bed?->id),
            ],
            'bed_type' => ['required', Rule::in(self::BED_TYPES)],
            'status' => ['sometimes', Rule::in([...self::MANUAL_STATUSES, 'occupied'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // "occupied" may only be echoed back unchanged for an already-occupied bed.
        if (($data['status'] ?? null) === 'occupied' && $bed?->status !== 'occupied') {
            throw ValidationException::withMessages([
                'status' => 'A bed can only become occupied through patient admission.',
            ]);
        }

        $data['ward_id'] = (int) $data['ward_id'];
        $data['room_id'] = isset($data['room_id']) ? (int) $data['room_id'] : null;

        return $data;
    }
}
