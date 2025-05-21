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
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade'); 
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label')->nullable(); // Unique name for the platform
            $table->string('provider'); // Type of platform (e.g., 'facebook', 'instagram', 'x')
            $table->string('external_id')->unique()->nullable();   // Page ID, Instagram ID, or Twitter handle
            $table->string('external_name')->nullable(); // Page name, Instagram username, or Twitter handle
            $table->string('external_url')->nullable();  // URL to the page
            $table->string('external_token')->nullable();  // Platform-specific token
            $table->text('external_picture_url')->nullable();  // Platform-specific token
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
