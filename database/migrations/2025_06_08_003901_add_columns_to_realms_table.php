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
            $table->unsignedInteger('orders_polling_frequency')->default(60)->after('orders_pull')->description(
                'Frequency in seconds for polling orders. Default is 60 seconds. If 0, polling is disabled.'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn('orders_polling_frequency');
        });
    }
};
