<?php

namespace App\Http\Controllers;

use App\Models\ChargeItem;
use App\Models\InvoiceLine;
use App\Support\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChargeItemController extends Controller
{
    public const CATEGORIES = ['consultation', 'procedure', 'medicine', 'bed', 'lab', 'radiology', 'other'];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::in(self::CATEGORIES)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $items = ChargeItem::query()
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('is_active', $s === 'active'))
            ->when($filters['search'] ?? null, function ($q, $s) {
                $like = '%'.addcslashes($s, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // Flag items referenced by invoice lines: these can only be deactivated.
        $usedIds = InvoiceLine::whereIn('charge_item_id', $items->pluck('id'))
            ->distinct()->pluck('charge_item_id')->all();

        $items->getCollection()->transform(function (ChargeItem $item) use ($usedIds) {
            $item->setAttribute('is_used', in_array($item->id, $usedIds, true));

            return $item;
        });

        return Inertia::render('Billing/ChargeItems/Index', [
            'items' => $items,
            'filters' => (object) array_filter($filters),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Billing/ChargeItems/Create', [
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ChargeItem::create($this->validated($request));

        return redirect('/billing/charge-items')->with('success', 'Charge item created.');
    }

    public function edit(ChargeItem $chargeItem): Response
    {
        return Inertia::render('Billing/ChargeItems/Edit', [
            'item' => $chargeItem,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function update(Request $request, ChargeItem $chargeItem): RedirectResponse
    {
        $chargeItem->update($this->validated($request, $chargeItem));

        return redirect('/billing/charge-items')->with('success', 'Charge item updated.');
    }

    /**
     * Delete an unused charge item; deactivate one that any invoice line references
     * so historical invoices keep their link to the price list.
     */
    public function destroy(ChargeItem $chargeItem): RedirectResponse
    {
        if (InvoiceLine::where('charge_item_id', $chargeItem->id)->exists()) {
            $chargeItem->update(['is_active' => false]);

            return back()->with('success', 'Charge item is used on invoices, so it was deactivated instead of deleted.');
        }

        $chargeItem->delete();

        return back()->with('success', 'Charge item deleted.');
    }

    private function validated(Request $request, ?ChargeItem $chargeItem = null): array
    {
        // Codes are case-insensitive identifiers: normalise before the uniqueness check.
        if (is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }

        $tenantId = app(TenantManager::class)->current()->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('charge_items', 'code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($chargeItem?->id),
            ],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'unit_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'tax_rate' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ], [
            'code.regex' => 'The code may only contain letters, numbers, dots, dashes and underscores.',
        ]);

        $data['tax_rate'] = $data['tax_rate'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}
