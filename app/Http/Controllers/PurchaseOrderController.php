<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = PurchaseOrder::with('supplier')
            ->withCount('items')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('PurchaseOrders/Index', [
            'orders' => $orders,
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): Response
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $medicines = Medicine::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit_type']);

        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => $suppliers,
            'medicines' => $medicines,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string',
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $po = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'po_number' => 'PO-'.strtoupper(Str::random(8)),
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'medicine_id' => $item['medicine_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_price' => $item['unit_price'],
                ]);
            }
        });

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order created.');
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['supplier', 'createdBy', 'items.medicine']);

        return Inertia::render('PurchaseOrders/Show', ['order' => $purchaseOrder]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,received,cancelled',
        ]);

        $purchaseOrder->update([
            'status' => $validated['status'],
            'received_at' => $validated['status'] === 'received' ? now() : $purchaseOrder->received_at,
            'ordered_at' => $validated['status'] === 'sent' && ! $purchaseOrder->ordered_at ? now() : $purchaseOrder->ordered_at,
        ]);

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order updated.');
    }
}
