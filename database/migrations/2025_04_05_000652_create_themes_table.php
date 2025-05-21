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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            // $table->json('fonts')->nullable();
            // $table->json('font_sizes')->nullable();
            // $table->json('colors')->nullable();
            $table->json('paddings')->nullable();
            $table->json('background_images')->nullable();
             // Add content styling fields
            $table->string('content_font_family')->nullable();
            $table->integer('content_font_size')->nullable();
            $table->string('content_font_color')->nullable();
            
            // Add author styling fields
            $table->string('author_font_family')->nullable();
            $table->integer('author_font_size')->nullable();
            $table->string('author_font_color')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
