<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->decimal('lat', 10, 8)->nullable()->after('name');
            $table->decimal('lon', 11, 8)->nullable()->after('lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lon']);
        });
    }
};
