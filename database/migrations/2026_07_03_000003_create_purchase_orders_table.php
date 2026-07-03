<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('po_number', 30);
            $table->enum('status', ['draft', 'sent', 'received', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'po_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
