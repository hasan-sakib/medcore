<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->enum('note_type', ['soap', 'progress', 'procedure', 'discharge_summary', 'referral'])
                ->default('soap');

            // SOAP fields — all PHI-encrypted (AES-256-GCM ciphertext as TEXT)
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();

            // Free-text body for non-SOAP note types — encrypted
            $table->text('body')->nullable();

            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'encounter_id'], 'idx_cn_tenant_encounter');
            $table->index(['tenant_id', 'author_id'], 'idx_cn_tenant_author');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_notes');
    }
};
