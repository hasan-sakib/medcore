<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->comment('created|updated|deleted|phi_read:<context>');
            $table->string('auditable_type', 150);
            $table->unsignedBigInteger('auditable_id');
            $table->json('changes')->nullable()->comment('PHI redacted to field names only');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            // No updated_at — append-only; DB user should not have UPDATE/DELETE on this table
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id'], 'audit_morphable_index');
            $table->index(['tenant_id', 'user_id', 'created_at'], 'audit_tenant_user_time_index');
            $table->index(['tenant_id', 'action', 'created_at'], 'audit_tenant_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
