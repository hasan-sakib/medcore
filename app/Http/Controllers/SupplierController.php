<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(): Response
    {
        $suppliers = Supplier::withCount('medicineBatches')
            ->orderBy('name')
            ->paginate(25);

        return Inertia::render('Suppliers/Index', ['suppliers' => $suppliers]);
    }

    public function create(): Response
    {
        return Inertia::render('Suppliers/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'contact_name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string',
        ]);

        Supplier::create($validated);

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier created.');
    }

    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('Suppliers/Edit', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'contact_name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['is_active' => false]);

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier deactivated.');
    }
}
