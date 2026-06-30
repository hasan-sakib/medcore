<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnosis_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['primary', 'secondary', 'complication', 'admitting'])->default('primary');
            $table->date('onset_date')->nullable();
            $table->date('resolved_at')->nullable();
            $table->text('notes')->nullable(); // PHI-encrypted (clinical annotation)
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['encounter_id', 'diagnosis_id']);
            $table->index(['tenant_id', 'encounter_id'], 'idx_ed_tenant_encounter');
            $table->index(['tenant_id', 'diagnosis_id'], 'idx_ed_tenant_diagnosis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_diagnoses');
    }
};
