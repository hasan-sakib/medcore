<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attending_doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('encounter_type', ['outpatient', 'inpatient', 'emergency', 'teleconsult'])
                ->default('outpatient');
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])
                ->default('open');
            $table->text('chief_complaint')->nullable();
            $table->date('encounter_date');
            $table->timestamp('admitted_at')->nullable();
            $table->timestamp('discharged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'patient_id'], 'idx_enc_tenant_patient');
            $table->index(['tenant_id', 'attending_doctor_id', 'encounter_date'], 'idx_enc_tenant_doctor_date');
            $table->index(['tenant_id', 'status'], 'idx_enc_tenant_status');
            $table->index(['tenant_id', 'encounter_date'], 'idx_enc_tenant_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
