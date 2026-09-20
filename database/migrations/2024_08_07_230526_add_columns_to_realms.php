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
            // Nina
            $table->string('nina_ars', 13)->nullable()->before('created_at');
            // OpenWeather
            $table->string('ow_api_key', 32)->nullable()->before('created_at');
            $table->string('ow_city_id', 20)->nullable()->before('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->removeColumn('nina_ars');
            $table->removeColumn('ow_api_key');
            $table->removeColumn('ow_city_id');
        });
    }
};
