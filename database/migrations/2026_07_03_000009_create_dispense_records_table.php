<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispense_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prescription_item_id')->nullable()->constrained('prescription_items')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('medicine_batches')->cascadeOnDelete();
            $table->unsignedInteger('quantity_dispensed');
            $table->foreignId('dispensed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dispensed_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id', 'dispensed_at'], 'idx_dispense_patient');
            $table->index(['tenant_id', 'prescription_id'], 'idx_dispense_rx');
            $table->index(['tenant_id', 'batch_id'], 'idx_dispense_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispense_records');
    }
};
