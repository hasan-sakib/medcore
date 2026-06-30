<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('mrn', 30); // Medical Record Number

            // PHI-encrypted columns (AES-256-GCM ciphertext stored as TEXT)
            $table->text('first_name');
            $table->text('last_name');
            $table->text('date_of_birth');
            $table->text('gender')->nullable();
            $table->text('national_id')->nullable();
            $table->text('phone')->nullable();
            $table->text('email')->nullable();
            $table->text('address')->nullable();
            $table->text('blood_group')->nullable();
            $table->text('emergency_contact')->nullable();

            // Blind-index columns for exact-match PHI lookup (HMAC-SHA256 hex)
            $table->char('first_name_index', 64)->nullable();
            $table->char('last_name_index', 64)->nullable();
            $table->char('national_id_index', 64)->nullable();
            $table->char('phone_index', 64)->nullable();

            // Non-PHI columns (plaintext, queryable)
            $table->enum('status', ['active', 'inactive', 'deceased'])->default('active');
            $table->timestamp('registered_at')->useCurrent();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'mrn']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'first_name_index'], 'idx_patient_bi_fname');
            $table->index(['tenant_id', 'last_name_index'], 'idx_patient_bi_lname');
            $table->index(['tenant_id', 'national_id_index'], 'idx_patient_bi_natid');
            $table->index(['tenant_id', 'phone_index'], 'idx_patient_bi_phone');
            $table->index(['tenant_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
