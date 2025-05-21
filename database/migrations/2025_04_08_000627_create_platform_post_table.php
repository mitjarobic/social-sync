<?php

use App\Enums\PlatformPostStatus;
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
        Schema::create('platform_post', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('platform_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->constrained()->onDelete('cascade');

            $table->enum('status', PlatformPostStatus::values())
                ->default(PlatformPostStatus::DRAFT->value)
                ->index();

            // External data fields
            $table->string('external_id')->nullable()->index();
            $table->string('external_url')->nullable();

            // Status tracking
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            // Metrics
            $table->integer('reach')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->timestamp('metrics_updated_at')->nullable();

            // Indexes for performance
            $table->index(['post_id', 'status']);
            $table->index(['platform_id', 'status']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_post');
    }
};
