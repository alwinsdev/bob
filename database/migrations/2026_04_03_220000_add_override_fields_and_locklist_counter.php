<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Lock List override tracking fields & batch-level locklist counter.
     *
     * reconciliation_queue:
     *   - override_flag    : true when a Lock List entry was applied
     *   - override_source  : 'lock_list' (or null if no override)
     *
     * import_batches:
     *   - locklist_matched_records : number of records overridden by Lock List in this run
     */
    public function up(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            $table->boolean('override_flag')->default(false)->after('status');
            $table->string('override_source')->nullable()->after('override_flag');
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->unsignedInteger('locklist_matched_records')->default(0)->after('hs_matched_records');
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            $table->dropColumn(['override_flag', 'override_source']);
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn('locklist_matched_records');
        });
    }
};
