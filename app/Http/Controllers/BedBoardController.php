<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Ward;
use App\Services\BedAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BedBoardController extends Controller
{
    public function __construct(private BedAllocationService $service) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Bed::class);

        $wards = Ward::where('is_active', true)
            ->with([
                'beds' => fn ($q) => $q->where('is_active', true)->orderBy('bed_number'),
                'beds.room',
                'beds.currentAllocation.patient',
            ])
            ->withCount([
                'beds as total_beds' => fn ($q) => $q->where('is_active', true),
                'beds as available_beds' => fn ($q) => $q->where('is_active', true)->where('status', 'available'),
                'beds as occupied_beds' => fn ($q) => $q->where('is_active', true)->where('status', 'occupied'),
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('Beds/Board', [
            'wards' => $wards,
        ]);
    }

    public function markAvailable(Bed $bed): RedirectResponse
    {
        $this->authorize('update', $bed);
        $this->service->markAvailable($bed);

        return back()->with('success', "Bed {$bed->bed_number} marked available.");
    }

    public function toggleMaintenance(Bed $bed): RedirectResponse
    {
        $this->authorize('update', $bed);
        $this->service->toggleMaintenance($bed);

        return back()->with('success', "Bed {$bed->bed_number} status updated.");
    }
}
