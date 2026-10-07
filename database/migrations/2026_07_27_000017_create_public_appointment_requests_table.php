<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('patient_name', 200);
            $table->string('patient_phone', 30);
            $table->string('patient_email', 200)->nullable();
            $table->date('preferred_date')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'contacted', 'booked', 'cancelled'])->default('pending')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_appointment_requests');
    }
};
