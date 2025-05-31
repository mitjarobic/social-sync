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
        Schema::create('image_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('background_type', ['color', 'image'])->default('color');
            $table->string('background_color')->nullable();
            $table->string('background_image')->nullable();
            // $table->string('font_family')->nullable();
            // $table->integer('font_size')->nullable();
            // $table->string('font_color')->nullable();
            $table->enum('text_alignment', ['left', 'center', 'right'])->default('center');
            $table->enum('text_position', ['top', 'middle', 'bottom'])->default('middle');
            $table->integer('padding')->default(20);
            // Add content styling fields
            $table->string('content_font_family')->nullable();
            $table->integer('content_font_size')->nullable();
            $table->string('content_font_color')->nullable();            
            // Add author styling fields
            $table->string('author_font_family')->nullable();
            $table->integer('author_font_size')->nullable();
            $table->string('author_font_color')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_templates');
    }
};
