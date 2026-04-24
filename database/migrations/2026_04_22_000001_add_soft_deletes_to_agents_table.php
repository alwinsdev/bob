<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add soft deletes to the agents table.
 *
 * Rationale: Hard-deleting an agent record would orphan all historical
 * ResolutionQueue records and audit logs that reference that agent.
 * Soft deletes preserve the foreign key integrity while hiding the
 * agent from active UI lookups (via the Agent model's global SoftDeletes scope).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // Adds nullable `deleted_at` timestamp column required by SoftDeletes trait
            $table->softDeletes()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
