<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('provider_name', 200);
            $table->string('policy_number', 100);
            $table->string('group_number', 100)->nullable();
            $table->string('coverage_type', 100)->default('individual');
            $table->decimal('coverage_limit', 12, 2)->nullable();
            $table->decimal('copay_amount', 10, 2)->default(0);
            $table->decimal('deductible_amount', 10, 2)->default(0);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
