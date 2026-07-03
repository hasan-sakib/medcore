<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('generic_name', 200)->nullable();
            $table->string('sku', 50);
            $table->string('category', 100)->nullable();
            $table->enum('unit_type', ['tablet', 'capsule', 'ml', 'unit', 'vial'])->default('tablet');
            $table->string('strength', 50)->nullable();
            $table->unsignedInteger('min_stock_level')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
