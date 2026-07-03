<?php

namespace App\Http\Controllers;

use App\Models\DispenseRecord;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\PrescriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DispenseController extends Controller
{
    public function __construct(private PrescriptionService $prescriptions) {}

    public function index(Request $request): Response
    {
        $this->authorize('create', DispenseRecord::class);

        $prescriptions = Prescription::with(['patient', 'prescribedBy', 'items.medicine'])
            ->whereIn('status', ['pending', 'partially_filled'])
            ->when($request->search, fn ($q, $s) => $q->whereHas('patient', fn ($pq) => $pq->where('mrn', 'like', '%'.$s.'%')))
            ->orderBy('prescribed_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Pharmacy/Dispense', [
            'prescriptions' => $prescriptions,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DispenseRecord::class);

        $validated = $request->validate([
            'prescription_item_id' => 'required|exists:prescription_items,id',
        ]);

        $item = PrescriptionItem::with('prescription')->findOrFail($validated['prescription_item_id']);

        try {
            $this->prescriptions->fillItem($item, Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Item dispensed successfully.');
    }
}
