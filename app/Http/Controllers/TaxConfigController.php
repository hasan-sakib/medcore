<?php

namespace App\Http\Controllers;

use App\Models\TaxConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaxConfigController extends Controller
{
    public const APPLIES_TO = ['all', 'consultation', 'medicine', 'procedure', 'bed'];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'applies_to' => ['nullable', Rule::in(self::APPLIES_TO)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $configs = TaxConfig::query()
            ->when($filters['applies_to'] ?? null, fn ($q, $a) => $q->where('applies_to', $a))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('is_active', $s === 'active'))
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where('name', 'like', '%'.addcslashes($s, '%_\\').'%');
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Billing/TaxConfigs/Index', [
            'configs' => $configs,
            'filters' => (object) array_filter($filters),
            'appliesTo' => self::APPLIES_TO,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Billing/TaxConfigs/Create', ['appliesTo' => self::APPLIES_TO]);
    }

    public function store(Request $request): RedirectResponse
    {
        TaxConfig::create($this->validated($request));

        return redirect('/billing/tax-configs')->with('success', 'Tax configuration created.');
    }

    public function edit(TaxConfig $taxConfig): Response
    {
        return Inertia::render('Billing/TaxConfigs/Edit', [
            'config' => $taxConfig,
            'appliesTo' => self::APPLIES_TO,
        ]);
    }

    public function update(Request $request, TaxConfig $taxConfig): RedirectResponse
    {
        $taxConfig->update($this->validated($request));

        return redirect('/billing/tax-configs')->with('success', 'Tax configuration updated.');
    }

    /**
     * Invoice lines snapshot the tax rate rather than referencing a tax config, so there is
     * no reliable way to prove a config is "unused". Tax configs are therefore never
     * hard-deleted; they are deactivated.
     */
    public function destroy(TaxConfig $taxConfig): RedirectResponse
    {
        $taxConfig->update(['is_active' => false]);

        return back()->with('success', 'Tax configuration deactivated.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'applies_to' => ['required', Rule::in(self::APPLIES_TO)],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}
