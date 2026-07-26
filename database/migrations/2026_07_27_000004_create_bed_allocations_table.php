<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bed_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('allocated_by')->constrained('users');
            $table->timestamp('admitted_at');
            $table->timestamp('discharged_at')->nullable();
            $table->string('discharge_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'bed_id', 'discharged_at']);
            $table->index(['tenant_id', 'patient_id', 'discharged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_allocations');
    }
};
