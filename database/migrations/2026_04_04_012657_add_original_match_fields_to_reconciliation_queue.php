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
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            $table->string('original_agent_name')->nullable()->after('override_source');
            $table->string('original_match_method')->nullable()->after('original_agent_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            $table->dropColumn(['original_agent_name', 'original_match_method']);
        });
    }
};
