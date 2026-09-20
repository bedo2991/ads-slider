<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('picture_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('picture_id')->constrained('pictures')->onDelete('cascade');
            $table->string('path');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->timestamps();
        });

        $pictures = DB::table('pictures')->get();
        // Use config but fallback to default only if config returns null
        $basePath = config('ads.pic_basepath') ?: '/uploads/pics/';

        foreach ($pictures as $pic) {
            // Need to handle if pic->path is null or empty, though it shouldn't be based on schema
            if (empty($pic->path)) {
                continue;
            }

            $relativePath = $basePath.$pic->path;
            $width = null;
            $height = null;

            // Check if file exists in the public disk
            if (Storage::disk('public')->exists($relativePath)) {
                $fullPath = Storage::disk('public')->path($relativePath);
                try {
                    $size = getimagesize($fullPath);
                    if ($size) {
                        $width = $size[0];
                        $height = $size[1];
                    }
                } catch (Exception $e) {
                    // Ignore errors if file is corrupted
                }
            }

            DB::table('picture_sources')->insert([
                'picture_id' => $pic->id,
                'path' => $pic->path,
                'width' => $width,
                'height' => $height,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('pictures', function (Blueprint $table) {
            $table->dropColumn('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pictures', function (Blueprint $table) {
            $table->string('path')->nullable();
        });

        $sources = DB::table('picture_sources')->get();

        foreach ($sources as $source) {
            // Restore path to pictures table.
            // If multiple sources exist, this logic naively takes one of them (the last one usually, or arbitrary),
            // which is acceptable for a direct reversal of the up() method during development.
            DB::table('pictures')
                ->where('id', $source->picture_id)
                ->update(['path' => $source->path]);
        }

        // We cannot easily restore NOT NULL constraint without ensuring data integrity first,
        // but let's try to restore it if we want strict reversal.
        // Assuming all pictures got a path back.
        // Schema::table('pictures', function (Blueprint $table) {
        //     $table->string('path')->nullable(false)->change();
        // });

        Schema::dropIfExists('picture_sources');
    }
};
