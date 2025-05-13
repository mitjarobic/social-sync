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
        Schema::table('platform_post', function (Blueprint $table) {
            // Add metrics columns
            $table->integer('reach')->default(0)->after('metadata');
            $table->integer('likes')->default(0)->after('reach');
            $table->integer('comments')->default(0)->after('likes');
            $table->integer('shares')->default(0)->after('comments');
            $table->timestamp('metrics_updated_at')->nullable()->after('shares');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_post', function (Blueprint $table) {
            $table->dropColumn([
                'reach',
                'likes',
                'comments',
                'shares',
                'metrics_updated_at'
            ]);
        });
    }
};
