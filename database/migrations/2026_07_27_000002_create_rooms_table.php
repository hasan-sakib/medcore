<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->string('room_number', 20);
            $table->string('room_type', 30)->default('general'); // general|private|icu|isolation
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'room_number']);
            $table->index(['tenant_id', 'ward_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
