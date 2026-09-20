<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorPictureTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitor_picture', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('monitor_id');
            $table->unsignedBigInteger('picture_id');
            $table->foreign('monitor_id')->references('id')->on('monitors');
            $table->foreign('picture_id')->references('id')->on('pictures');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monitor_picture');
    }
}
