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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('image_font')->nullable()->default('sansSerif.ttf');
            $table->integer('image_font_size')->nullable()->default(112);
            $table->string('image_font_color')->nullable()->default('#FFFFFF');
            $table->string('image_bg_color')->nullable()->default('#000000');
            $table->string('image_bg_image_path')->nullable();
            $table->json('image_options')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'image_font',
                'image_font_size',
                'image_font_color',
                'image_bg_color',
                'image_bg_image_path',
                'image_options',
            ]);
        });
    }
};
