<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at')->useCurrent();

            // Numeric measurements — NOT encrypted (aggregatable, not directly identifying)
            $table->decimal('temperature_c', 4, 1)->nullable();   // e.g. 37.2 °C
            $table->unsignedSmallInteger('pulse_bpm')->nullable();
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->decimal('spo2_pct', 4, 1)->nullable();         // e.g. 98.5 %
            $table->unsignedTinyInteger('respiratory_rate')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('bmi', 4, 1)->nullable();              // computed, stored for query efficiency
            $table->decimal('glucose_mmol', 5, 2)->nullable();
            $table->unsignedTinyInteger('pain_scale')->nullable(); // 0–10
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'encounter_id'], 'idx_vital_tenant_encounter');
            $table->index(['tenant_id', 'patient_id', 'recorded_at'], 'idx_vital_tenant_patient_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals');
    }
};
