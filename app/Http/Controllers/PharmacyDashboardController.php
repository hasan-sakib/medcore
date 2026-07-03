<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Services\InventoryService;
use Inertia\Inertia;
use Inertia\Response;

class PharmacyDashboardController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(): Response
    {
        $totalMedicines = Medicine::where('is_active', true)->count();
        $expiryAlerts = $this->inventory->getExpiryAlerts(30);
        $lowStockAlerts = $this->inventory->getLowStockAlerts();

        return Inertia::render('Pharmacy/Dashboard', [
            'stats' => [
                'total_medicines' => $totalMedicines,
                'expiring_soon_count' => $expiryAlerts->count(),
                'low_stock_count' => $lowStockAlerts->count(),
            ],
            'expiryAlerts' => $expiryAlerts,
            'lowStockAlerts' => $lowStockAlerts,
        ]);
    }
}
