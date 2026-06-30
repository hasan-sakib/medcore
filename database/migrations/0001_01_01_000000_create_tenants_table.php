<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 63)->unique()->comment('Subdomain slug, e.g. citygeneral');
            $table->string('domain')->nullable()->unique()->comment('Custom domain if applicable');
            $table->enum('status', ['active', 'suspended', 'trial'])->default('trial')->index();
            $table->string('subscription_plan', 50)->default('trial');
            $table->json('settings')->nullable()->comment('Tenant-specific config overrides');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
