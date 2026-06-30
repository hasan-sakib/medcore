<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->dateTime('ends_at');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'])
                ->default('pending');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite index for slot-conflict check: WHERE tenant_id=? AND doctor_id=? AND scheduled_at < ? AND ends_at > ?
            $table->index(['tenant_id', 'doctor_id', 'scheduled_at', 'ends_at'], 'idx_appt_tenant_doctor_slot');
            $table->index(['tenant_id', 'patient_id'], 'idx_appt_tenant_patient');
            $table->index(['tenant_id', 'status', 'scheduled_at'], 'idx_appt_tenant_status_sched');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
