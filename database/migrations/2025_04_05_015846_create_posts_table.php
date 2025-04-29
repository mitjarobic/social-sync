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
