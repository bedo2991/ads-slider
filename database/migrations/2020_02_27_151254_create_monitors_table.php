<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50);
            $table->integer('events_to_show')->default(8);
            $table->boolean('show_preparation_countdowns');
            $table->boolean('show_final_rounds');
            $table->boolean('show_we_are_closing');
            $table->boolean('show_we_are_closed_marketing');
            $table->boolean('show_cancelled_events');
            $table->boolean('show_menus');
            $table->boolean('show_happy_hours');
            $table->boolean('show_pictures');
            $table->boolean('show_karaoke');
            $table->boolean('show_weather_forecast');
            $table->boolean('use_animations');
            $table->boolean('show_marquee');
            $table->boolean('show_event_while_is_happening');
            $table->string('api_token', 80)->unique()->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monitors');
    }
}
