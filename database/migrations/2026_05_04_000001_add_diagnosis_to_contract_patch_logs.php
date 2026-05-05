<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a free-text diagnosis column to contract_patch_logs so the
 * Payee Back-Flow Analysis engine can persist a human-readable trace
 * for every cascade decision (Resolved / Unresolved / Lock Override / Failed).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('contract_patch_logs', function (Blueprint $table) {
            $table->text('diagnosis')->nullable()->after('flag_value')
                ->comment('Human-readable explanation of the cascade decision (source + match key used)');
            $table->string('match_key', 100)->nullable()->after('new_match_source')
                ->comment('Identity key that fired the match: Email, Phone, Name, DOB+Last, ContractID, etc.');
        });
    }

    public function down(): void
    {
        Schema::table('contract_patch_logs', function (Blueprint $table) {
            $table->dropColumn(['diagnosis', 'match_key']);
        });
    }
};
