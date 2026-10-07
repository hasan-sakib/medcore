<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('tagline', 255)->nullable()->after('name');
            $table->text('description')->nullable()->after('tagline');
            $table->string('address', 500)->nullable()->after('description');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('phone', 30)->nullable()->after('city');
            $table->string('email', 150)->nullable()->after('phone');
            $table->string('website', 255)->nullable()->after('email');
            $table->string('logo_url', 500)->nullable()->after('website');
            $table->json('features')->nullable()->comment('Array of feature/service strings, e.g. ["ICU","Emergency","Pharmacy"]')->after('logo_url');
            $table->boolean('is_publicly_listed')->default(true)->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'tagline', 'description', 'address', 'city',
                'phone', 'email', 'website', 'logo_url',
                'features', 'is_publicly_listed',
            ]);
        });
    }
};
