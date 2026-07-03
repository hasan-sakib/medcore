<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $movements = StockMovement::with(['medicine', 'batch', 'createdBy'])
            ->when($request->medicine_id, fn ($q, $id) => $q->where('medicine_id', $id))
            ->when($request->movement_type, fn ($q, $t) => $q->where('movement_type', $t))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        $medicines = Medicine::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Pharmacy/StockMovementLog', [
            'movements' => $movements,
            'medicines' => $medicines,
            'filters' => $request->only('medicine_id', 'movement_type', 'from', 'to'),
        ]);
    }
}
