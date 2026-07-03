<?php

namespace App\Services;

use App\Models\DispenseRecord;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Prescription;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Deduct stock using FEFO (First Expiry First Out) inside a single transaction.
     * Throws \RuntimeException if total on-hand stock < requested quantity.
     *
     * @return Collection<int, DispenseRecord>
     */
    public function deductStockFEFO(
        int $medicineId,
        int $quantity,
        int $dispensedBy,
        ?int $prescriptionId = null,
        ?int $prescriptionItemId = null
    ): Collection {
        return DB::transaction(function () use ($medicineId, $quantity, $dispensedBy, $prescriptionId, $prescriptionItemId): Collection {
            $batches = MedicineBatch::activeFEFO()
                ->where('medicine_id', $medicineId)
                ->lockForUpdate()
                ->get();

            $totalAvailable = $batches->sum('quantity_on_hand');

            if ($totalAvailable < $quantity) {
                throw new \RuntimeException('Insufficient stock. Available: '.$totalAvailable.', Requested: '.$quantity.'.');
            }

            $records = collect();
            $remaining = $quantity;

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, $batch->quantity_on_hand);

                $batch->quantity_on_hand -= $take;
                if ($batch->quantity_on_hand === 0) {
                    $batch->status = 'depleted';
                }
                $batch->save();

                StockMovement::create([
                    'medicine_id' => $medicineId,
                    'batch_id' => $batch->id,
                    'movement_type' => 'out',
                    'quantity' => -$take,
                    'reference_type' => $prescriptionId ? 'prescription' : null,
                    'reference_id' => $prescriptionId,
                    'notes' => null,
                    'created_by' => $dispensedBy,
                ]);

                $record = DispenseRecord::create([
                    'prescription_id' => $prescriptionId,
                    'prescription_item_id' => $prescriptionItemId,
                    'patient_id' => $this->resolvePatientId($prescriptionId),
                    'medicine_id' => $medicineId,
                    'batch_id' => $batch->id,
                    'quantity_dispensed' => $take,
                    'dispensed_by' => $dispensedBy,
                    'notes' => null,
                ]);

                $records->push($record);
                $remaining -= $take;
            }

            return $records;
        });
    }

    /**
     * Record a goods-received note (batch intake). Creates the batch and a stock-in movement.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordBatchIntake(array $data): MedicineBatch
    {
        return DB::transaction(function () use ($data): MedicineBatch {
            $batch = MedicineBatch::create([
                'medicine_id' => $data['medicine_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'batch_number' => $data['batch_number'],
                'lot_number' => $data['lot_number'] ?? null,
                'quantity_received' => $data['quantity'],
                'quantity_on_hand' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'expiry_date' => $data['expiry_date'],
                'manufactured_date' => $data['manufactured_date'] ?? null,
                'received_by' => Auth::id(),
                'status' => 'active',
            ]);

            StockMovement::create([
                'medicine_id' => $batch->medicine_id,
                'batch_id' => $batch->id,
                'movement_type' => 'in',
                'quantity' => $batch->quantity_received,
                'reference_type' => $batch->purchase_order_id ? 'purchase_order' : null,
                'reference_id' => $batch->purchase_order_id,
                'notes' => 'Batch intake',
                'created_by' => Auth::id(),
            ]);

            // Update PO item quantity_received if linked
            if ($batch->purchase_order_id) {
                PurchaseOrderItem::where('purchase_order_id', $batch->purchase_order_id)
                    ->where('medicine_id', $batch->medicine_id)
                    ->increment('quantity_received', $batch->quantity_received);
            }

            return $batch->fresh();
        });
    }

    /**
     * Apply a stock count correction (positive delta = add stock, negative = remove).
     */
    public function adjustStock(MedicineBatch $batch, int $delta, string $reason, int $userId): StockMovement
    {
        return DB::transaction(function () use ($batch, $delta, $reason, $userId): StockMovement {
            $batch->lockForUpdate()->first();
            $batch->quantity_on_hand = max(0, $batch->quantity_on_hand + $delta);
            if ($batch->quantity_on_hand === 0) {
                $batch->status = 'depleted';
            } elseif ($batch->status === 'depleted') {
                $batch->status = 'active';
            }
            $batch->save();

            return StockMovement::create([
                'medicine_id' => $batch->medicine_id,
                'batch_id' => $batch->id,
                'movement_type' => 'adjustment',
                'quantity' => $delta,
                'notes' => $reason,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Batches expiring within $daysAhead days (active only).
     *
     * @return Collection<int, MedicineBatch>
     */
    public function getExpiryAlerts(int $daysAhead = 30): Collection
    {
        return MedicineBatch::with('medicine')
            ->where('status', 'active')
            ->where('quantity_on_hand', '>', 0)
            ->where('expiry_date', '<=', now()->addDays($daysAhead)->toDateString())
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Medicines where total on-hand stock is at or below their reorder_level.
     *
     * @return Collection<int, Medicine>
     */
    public function getLowStockAlerts(): Collection
    {
        return Medicine::withSum(['batches as stock_on_hand' => function ($q) {
            $q->where('status', 'active');
        }], 'quantity_on_hand')
            ->where('is_active', true)
            ->havingRaw('COALESCE(stock_on_hand, 0) <= medicines.reorder_level')
            ->orderBy('stock_on_hand')
            ->get();
    }

    private function resolvePatientId(?int $prescriptionId): ?int
    {
        if (! $prescriptionId) {
            return null;
        }

        $prescription = Prescription::find($prescriptionId);

        return $prescription ? $prescription->patient_id : null;
    }
}
