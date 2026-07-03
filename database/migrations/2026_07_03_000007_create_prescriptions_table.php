<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prescribed_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'partially_filled', 'filled', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('prescribed_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'patient_id', 'status'], 'idx_rx_patient_status');
            $table->index(['tenant_id', 'prescribed_by'], 'idx_rx_prescriber');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
