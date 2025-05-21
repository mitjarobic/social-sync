<?php

use App\Enums\PostStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); // Foreign key to Company
            $table->string('content');
            $table->string('image_content')->nullable();
            $table->string('image_author')->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('image_template_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('use_custom_image_settings')->default(false);

            // $table->string('image_font')->nullable()->default('sansSerif.ttf');
            // $table->integer('image_font_size')->nullable()->default(80);
            // $table->string('image_font_color')->nullable()->default('#FFFFFF');

            $table->string('image_bg_color')->nullable()->default('#000000');
            $table->string('image_bg_image_path')->nullable();
            $table->json('image_options')->nullable();

            // Add content styling fields
            $table->string('content_font')->nullable();
            $table->integer('content_font_size')->nullable();
            $table->string('content_font_color')->nullable();
            
            // Add author styling fields
            $table->string('author_font')->nullable();
            $table->integer('author_font_size')->nullable();
            $table->string('author_font_color')->nullable();

            $table->enum('status', PostStatus::values())
                ->default(PostStatus::DRAFT->value)
                ->index();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
