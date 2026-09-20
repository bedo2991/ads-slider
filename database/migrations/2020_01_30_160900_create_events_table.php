<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->date('start')->nullable();
            $table->time('start_time');
            $table->date('end')->nullable();
            $table->time('end_time');
            $table->string('place')->nullable();
            $table->string('icon')->nullable();
            $table->string('repeat', 7)->nullable();
            $table->boolean('not_closing');
            $table->boolean('final_round_confirmed');
            $table->boolean('is_karaoke');
            $table->boolean('disabled');
            $table->string('color', 7)->default('#FFFFFF');
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
        Schema::dropIfExists('events');
    }
}
