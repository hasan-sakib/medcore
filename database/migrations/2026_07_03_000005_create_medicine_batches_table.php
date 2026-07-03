<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number', 50);
            $table->string('lot_number', 50)->nullable();
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_on_hand');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->date('expiry_date');
            $table->date('manufactured_date')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'expired', 'quarantined', 'depleted'])->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'batch_number']);
            // FEFO index: fetch active batches by medicine, oldest expiry first
            $table->index(['tenant_id', 'medicine_id', 'expiry_date', 'status'], 'idx_batch_fefo');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
    }
};
