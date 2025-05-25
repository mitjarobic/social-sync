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
        Schema::table('platforms', function (Blueprint $table) {
            // Add unique constraint for provider and company_id combination
            // This ensures only one platform per provider per company
            $table->unique(['provider', 'company_id'], 'unique_provider_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('unique_provider_company');
        });
    }
};
