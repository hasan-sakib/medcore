<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('medicine_batches')->cascadeOnDelete();
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'return', 'waste']);
            // Signed: positive = stock added, negative = stock removed
            $table->integer('quantity');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'medicine_id', 'created_at'], 'idx_stock_mvt_medicine');
            $table->index(['tenant_id', 'batch_id'], 'idx_stock_mvt_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
