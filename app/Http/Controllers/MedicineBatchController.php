<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicineBatchController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Medicine::class);

        $batches = MedicineBatch::with(['medicine', 'supplier'])
            ->when($request->medicine_id, fn ($q, $id) => $q->where('medicine_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('expiry_date')
            ->paginate(25)
            ->withQueryString();

        $medicines = Medicine::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);

        return Inertia::render('Pharmacy/BatchIntakeIndex', [
            'batches' => $batches,
            'medicines' => $medicines,
            'filters' => $request->only('medicine_id', 'status'),
        ]);
    }

    public function create(): Response
    {
        $medicines = Medicine::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit_type', 'strength']);
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $purchaseOrders = PurchaseOrder::where('status', '!=', 'cancelled')->orderBy('po_number')->get(['id', 'po_number']);

        return Inertia::render('Pharmacy/BatchIntake', [
            'medicines' => $medicines,
            'suppliers' => $suppliers,
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'medicine_id' => 'required|exists:medicines,id',
            'batch_number' => 'required|string|max:50',
            'lot_number' => 'nullable|string|max:50',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'expiry_date' => 'required|date|after:today',
            'manufactured_date' => 'nullable|date|before_or_equal:today',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
        ]);

        try {
            $this->inventory->recordBatchIntake($validated);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('medicine-batches.index')->with('success', 'Batch recorded.');
    }
}
