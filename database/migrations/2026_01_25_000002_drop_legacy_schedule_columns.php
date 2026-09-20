<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy columns from events
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['start', 'end', 'start_time', 'end_time', 'repeat', 'disabled']);
        });

        // Drop legacy columns from templates
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
            // Templates never had start/end/repeat date columns, just time
        });

        // Drop the slide tables completely as they are backed by Schedule + Picture/Video
        Schema::dropIfExists('picture_slides');
        Schema::dropIfExists('video_slides');
    }

    public function down(): void
    {
        // Re-adding columns is possible but data restoration requires backup.
        // This is a destructive operation.
        Schema::table('events', function (Blueprint $table) {
            $table->date('start')->nullable();
            $table->date('end')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('repeat', 7)->nullable();
            $table->boolean('disabled')->default(false);
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
        });

        // Recreating tables would require original definitions
    }
};
