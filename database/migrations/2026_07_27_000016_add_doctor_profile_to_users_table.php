<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('specialty', 150)->nullable()->after('name');
            $table->text('bio')->nullable()->after('specialty');
            $table->string('avatar_url', 500)->nullable()->after('bio');
            $table->boolean('is_publicly_listed')->default(true)->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['specialty', 'bio', 'avatar_url', 'is_publicly_listed']);
        });
    }
};
