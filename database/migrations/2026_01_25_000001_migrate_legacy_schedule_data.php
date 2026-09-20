<?php

use App\Models\Schedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Migrate Events
        // Events map 1:1 to a schedule (morphOne)
        $events = DB::table('events')->get();
        foreach ($events as $event) {
            DB::table('schedules')->insert([
                'start' => $event->start,
                'end' => $event->end,
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'repeat' => $event->repeat,
                'disabled' => $event->disabled ?? false,
                'scheduleable_id' => $event->id,
                'scheduleable_type' => 'EV',
                'realm_id' => $event->realm_id,
                'user_id' => $event->user_id,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ]);
        }

        // 2. Migrate Templates
        // Templates map 1:1 to a schedule (morphOne)
        $templates = DB::table('templates')->get();
        foreach ($templates as $template) {
            DB::table('schedules')->insert([
                'start' => null,
                'end' => null,
                'start_time' => $template->start_time,
                'end_time' => $template->end_time,
                'repeat' => null, // Templates will use this in future
                'disabled' => false,
                'scheduleable_id' => $template->id,
                'scheduleable_type' => 'TE',
                'realm_id' => $template->realm_id,
                'user_id' => $template->user_id,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ]);
        }

        // 3. Migrate PictureSlides
        // We need to join with pictures table to get realm_id
        $pSlides = DB::table('picture_slides')
            ->join('pictures', 'picture_slides.picture_id', '=', 'pictures.id')
            ->select('picture_slides.*', 'pictures.realm_id as picture_realm_id')
            ->get();

        foreach ($pSlides as $slide) {
            DB::table('schedules')->insert([
                'start' => $slide->start,
                'end' => $slide->end,
                'start_time' => $slide->start_time,
                'end_time' => $slide->end_time,
                'repeat' => $slide->repeat,
                'disabled' => $slide->disabled,
                'scheduleable_id' => $slide->picture_id,
                'scheduleable_type' => 'PI',
                'realm_id' => $slide->picture_realm_id,
                'user_id' => $slide->user_id,
                'created_at' => $slide->created_at,
                'updated_at' => $slide->updated_at,
            ]);
        }

        // 4. Migrate VideoSlides
        // We need to join with videos table to get realm_id
        $vSlides = DB::table('video_slides')
            ->join('videos', 'video_slides.video_id', '=', 'videos.id')
            ->select('video_slides.*', 'videos.realm_id as video_realm_id')
            ->get();

        foreach ($vSlides as $slide) {
            DB::table('schedules')->insert([
                'start' => $slide->start,
                'end' => $slide->end,
                'start_time' => $slide->start_time,
                'end_time' => $slide->end_time,
                'repeat' => $slide->repeat,
                'disabled' => $slide->disabled,
                'scheduleable_id' => $slide->video_id,
                'scheduleable_type' => 'VI',
                'realm_id' => $slide->video_realm_id,
                'user_id' => $slide->user_id,
                'created_at' => $slide->created_at,
                'updated_at' => $slide->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('schedules')->truncate();
    }
};
