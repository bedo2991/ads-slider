<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorVideoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitor_video', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('monitor_id');
            $table->unsignedBigInteger('video_id');
            $table->foreign('monitor_id')->references('id')->on('monitors');
            $table->foreign('video_id')->references('id')->on('videos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monitor_video');
    }
}
