<?php

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
        Schema::table('picture_sources', function (Blueprint $table) {
            $table->integer('clock_location')->default(5)->after('height'); // 5 is Bottom Right default
        });

        // Migrate data
        $pictures = DB::table('pictures')->get();
        foreach ($pictures as $pic) {
            DB::table('picture_sources')
                ->where('picture_id', $pic->id)
                ->update(['clock_location' => $pic->clock_location ?? 5]);
        }

        Schema::table('pictures', function (Blueprint $table) {
            $table->dropColumn('clock_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pictures', function (Blueprint $table) {
            $table->integer('clock_location')->default(5);
        });

        // Restore data
        $sources = DB::table('picture_sources')->get();
        // Naive restore: take the clock_location of the first source found for the picture
        foreach ($sources as $source) {
            DB::table('pictures')
                ->where('id', $source->picture_id)
                ->update(['clock_location' => $source->clock_location]);
        }

        Schema::table('picture_sources', function (Blueprint $table) {
            $table->dropColumn('clock_location');
        });
    }
};
