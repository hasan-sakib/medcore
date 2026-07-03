<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicineController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Medicine::class);

        $medicines = Medicine::withSum(['batches as stock_on_hand' => function ($q) {
            $q->where('status', 'active');
        }], 'quantity_on_hand')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', '%'.$s.'%')
                ->orWhere('sku', 'like', '%'.$s.'%'))
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Medicines/Index', ['medicines' => $medicines, 'filters' => $request->only('search', 'category')]);
    }

    public function create(): Response
    {
        $this->authorize('create', Medicine::class);

        return Inertia::render('Medicines/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Medicine::class);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'generic_name' => 'nullable|string|max:200',
            'sku' => 'required|string|max:50',
            'category' => 'nullable|string|max:100',
            'unit_type' => 'required|in:tablet,capsule,ml,unit,vial',
            'strength' => 'nullable|string|max:50',
            'min_stock_level' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
        ]);

        Medicine::create($validated);

        return redirect()->route('medicines.index')->with('success', 'Medicine created.');
    }

    public function show(Medicine $medicine): Response
    {
        $this->authorize('view', $medicine);

        $medicine->load(['batches' => fn ($q) => $q->where('status', 'active')->orderBy('expiry_date')]);

        return Inertia::render('Medicines/Show', ['medicine' => $medicine]);
    }

    public function edit(Medicine $medicine): Response
    {
        $this->authorize('update', $medicine);

        return Inertia::render('Medicines/Edit', ['medicine' => $medicine]);
    }

    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $this->authorize('update', $medicine);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'generic_name' => 'nullable|string|max:200',
            'category' => 'nullable|string|max:100',
            'unit_type' => 'required|in:tablet,capsule,ml,unit,vial',
            'strength' => 'nullable|string|max:50',
            'min_stock_level' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $medicine->update($validated);

        return redirect()->route('medicines.index')->with('success', 'Medicine updated.');
    }

    public function destroy(Medicine $medicine): RedirectResponse
    {
        $this->authorize('delete', $medicine);

        $medicine->update(['is_active' => false]);

        return redirect()->route('medicines.index')->with('success', 'Medicine deactivated.');
    }
}
