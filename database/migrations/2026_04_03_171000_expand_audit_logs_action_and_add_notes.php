<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert the 'action' column from ENUM to string to support new
     * actions (promoted_to_locklist, locklist_created, locklist_updated,
     * locklist_deleted, locklist_imported), and add a 'notes' column
     * for contextual audit information.
     */
    public function up(): void
    {
        // mysql doesn't support ALTER COLUMN directly.
        // We'll create a new column, copy data, drop old, rename.
        Schema::table('reconciliation_audit_logs', function (Blueprint $table) {
            $table->string('action_new')->nullable()->after('transaction_id');
            $table->string('notes')->nullable()->after('user_agent');
        });

        DB::table('reconciliation_audit_logs')->update([
            'action_new' => DB::raw('action'),
        ]);

        Schema::table('reconciliation_audit_logs', function (Blueprint $table) {
            $table->dropColumn('action');
        });

        Schema::table('reconciliation_audit_logs', function (Blueprint $table) {
            $table->renameColumn('action_new', 'action');
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_audit_logs', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
