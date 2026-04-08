<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add traceability columns to lock_lists:
     * - promoted_from_batch_id: which batch triggered this entry
     * - promoted_by: which user promoted it (or null if imported via file)
     */
    public function up(): void
    {
        Schema::table('lock_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('promoted_from_batch_id')->nullable()->after('payee_name');
            $table->unsignedBigInteger('promoted_by')->nullable()->after('promoted_from_batch_id');

            $table->index('promoted_from_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('lock_lists', function (Blueprint $table) {
            $table->dropIndex(['promoted_from_batch_id']);
            $table->dropColumn(['promoted_from_batch_id', 'promoted_by']);
        });
    }
};
