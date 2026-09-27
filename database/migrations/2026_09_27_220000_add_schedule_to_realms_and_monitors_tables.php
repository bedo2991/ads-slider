<?php

use App\Enums\ScheduledSlideType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->json('schedule')->nullable()->after('marketing_sentences');
        });

        Schema::table('monitors', function (Blueprint $table) {
            $table->json('schedule')->nullable()->after('marketing_sentences');
        });

        DB::table('realms')->update(['schedule' => json_encode(ScheduledSlideType::defaultSchedule())]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn('schedule');
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn('schedule');
        });
    }
};
