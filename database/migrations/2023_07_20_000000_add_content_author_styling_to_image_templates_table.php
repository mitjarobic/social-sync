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
        Schema::table('image_templates', function (Blueprint $table) {
            // Add content styling fields
            $table->string('content_font_family')->nullable()->after('font_color');
            $table->integer('content_font_size')->nullable()->after('content_font_family');
            $table->string('content_font_color')->nullable()->after('content_font_size');
            
            // Add author styling fields
            $table->string('author_font_family')->nullable()->after('content_font_color');
            $table->integer('author_font_size')->nullable()->after('author_font_family');
            $table->string('author_font_color')->nullable()->after('author_font_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_templates', function (Blueprint $table) {
            $table->dropColumn([
                'content_font_family',
                'content_font_size',
                'content_font_color',
                'author_font_family',
                'author_font_size',
                'author_font_color',
            ]);
        });
    }
};
