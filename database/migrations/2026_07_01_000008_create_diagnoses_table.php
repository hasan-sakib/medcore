<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global ICD-10 reference table — no tenant_id
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->string('icd10_code', 10)->unique();  // e.g. 'J18.9'
            $table->string('description', 255);          // 'Pneumonia, unspecified organism'
            $table->string('category', 100)->nullable(); // ICD-10 chapter/block label
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('icd10_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
