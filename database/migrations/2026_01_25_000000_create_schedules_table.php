<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // Date & Time (The "When")
            $table->date('start')->nullable()->index();
            $table->date('end')->nullable()->index();
            $table->time('start_time')->index();
            $table->time('end_time');

            // Recurrence & Status
            $table->string('repeat', 7)->nullable();
            $table->boolean('disabled')->default(false)->index();

            // Polymorphic Relation (The "What")
            // Creates `scheduleable_id` and `scheduleable_type` + Index
            $table->morphs('scheduleable');

            // Metadata
            $table->foreignId('realm_id')->constrained()->cascadeOnDelete();
            // If user is deleted, keep the schedule (set user_id to null)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
