<?php

namespace App\Http\Controllers;

use App\Models\Ward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WardController extends Controller
{
    public const WARD_TYPES = ['general', 'icu', 'maternity', 'pediatric', 'surgical', 'oncology', 'psychiatric'];

    /** Facilities overview: wards -> rooms -> beds (including inactive ones, for admins). */
    public function index(): Response
    {
        $wards = Ward::query()
            ->with([
                'rooms' => fn ($q) => $q->withCount('beds')->orderBy('room_number'),
                'beds' => fn ($q) => $q->withCount('allocations')->orderBy('bed_number'),
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('Facilities/Index', [
            'wards' => $wards,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Ward::create($data + ['is_active' => $data['is_active'] ?? true]);

        return back()->with('success', 'Ward created.');
    }

    public function update(Request $request, Ward $ward): RedirectResponse
    {
        $data = $this->validated($request, $ward);

        if (array_key_exists('is_active', $data) && ! $data['is_active'] && $ward->is_active) {
            if ($this->hasOccupiedBeds($ward)) {
                return back()->withErrors(['is_active' => 'Cannot deactivate a ward that has occupied beds.']);
            }
        }

        $ward->update($data);

        return back()->with('success', 'Ward updated.');
    }

    public function destroy(Ward $ward): RedirectResponse
    {
        if ($this->hasOccupiedBeds($ward)) {
            return back()->with('error', 'Cannot delete a ward that has occupied beds.');
        }

        if ($ward->rooms()->exists() || $ward->beds()->exists()) {
            return back()->with('error', 'Ward still has rooms or beds. Remove them first, or deactivate the ward instead.');
        }

        $ward->delete();

        return back()->with('success', 'Ward deleted.');
    }

    private function hasOccupiedBeds(Ward $ward): bool
    {
        return $ward->beds()
            ->where(fn ($q) => $q->where('status', 'occupied')
                ->orWhereHas('allocations', fn ($a) => $a->whereNull('discharged_at')))
            ->exists();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Ward $ward = null): array
    {
        $tenantId = $request->user()->tenant_id;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20', 'alpha_dash',
                Rule::unique('wards', 'code')->where('tenant_id', $tenantId)->ignore($ward?->id),
            ],
            'floor' => ['nullable', 'string', 'max:50'],
            'ward_type' => ['required', Rule::in(self::WARD_TYPES)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
