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
        Schema::create('events_imports', function (Blueprint $table) {
            $table->id();
            $table->string('import_name');
            $table->string('place')->nullable();
            $table->string('icon');
            $table->string('color', 7)->default('#FFFFFF');
            $table->string('marquee', 150)->nullable();
            $table->unsignedMediumInteger('preparation_time')->nullable();
            $table->boolean('not_closing');
            $table->boolean('final_round_confirmed');
            $table->boolean('is_karaoke')->default(false);
            $table->boolean('disabled');
            $table->boolean('import_disabled');
            $table->string('link', 1852)->nullable();
            $table->string('import_url', 1852);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events_imports');
    }
};
