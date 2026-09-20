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
        Schema::create('realms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('realm_id')->before('created_at')->nullable();
            $table->foreign('realm_id')->references('id')->on('realms')->cascadeOnDelete();
        });

        Schema::table('monitors', function (Blueprint $table) {
            $table->unsignedBigInteger('realm_id')->before('created_at')->nullable();
            $table->foreign('realm_id')->references('id')->on('realms')->cascadeOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('realm_id')->before('created_at')->nullable();
            $table->foreign('realm_id')->references('id')->on('realms')->cascadeOnDelete();
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->unsignedBigInteger('realm_id')->before('created_at')->nullable();
            $table->foreign('realm_id')->references('id')->on('realms')->cascadeOnDelete();
        });

        Schema::table('events_imports', function (Blueprint $table) {
            $table->unsignedBigInteger('realm_id')->before('created_at')->nullable();
            $table->foreign('realm_id')->references('id')->on('realms')->cascadeOnDelete();
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedBigInteger('realm_id')->before('created_at')->nullable();
            $table->foreign('realm_id')->references('id')->on('realms')->cascadeOnDelete();
        });

        Schema::table('pictures', function (Blueprint $table) {
            $table->unsignedBigInteger('realm_id')->before('created_at')->nullable();
            $table->foreign('realm_id')->references('id')->on('realms')->cascadeOnDelete();
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->unsignedBigInteger('realm_id')->before('created_at')->nullable();
            $table->foreign('realm_id')->references('id')->on('realms')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('realm_id');
            $table->dropForeign(['realm_id']);
        });

        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn('realm_id');
            $table->dropForeign(['realm_id']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('realm_id');
            $table->dropForeign(['realm_id']);
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('realm_id');
            $table->dropForeign(['realm_id']);
        });

        Schema::table('events_imports', function (Blueprint $table) {
            $table->dropColumn('realm_id');
            $table->dropForeign(['realm_id']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('realm_id');
            $table->dropForeign(['realm_id']);
        });

        Schema::table('pictures', function (Blueprint $table) {
            $table->dropColumn('realm_id');
            $table->dropForeign(['realm_id']);
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('realm_id');
            $table->dropForeign(['realm_id']);
        });

        Schema::dropIfExists('realms');
    }
};
