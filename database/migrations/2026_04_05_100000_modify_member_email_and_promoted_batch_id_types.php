<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix promoted_from_batch_id in lock_lists (unsignedBigInteger -> ulid string)
        if (Schema::hasTable('lock_lists') && Schema::hasColumn('lock_lists', 'promoted_from_batch_id')) {
            // mysql does not support altering column types natively without drop/recreate,
            // but we can add a new column, copy, and rename.
            Schema::table('lock_lists', function (Blueprint $table) {
                $table->ulid('promoted_from_batch_id_new')->nullable()->after('promoted_from_batch_id');
            });

            // Drop legacy index before dropping the original column (required for mysql).
            try {
                Schema::table('lock_lists', function (Blueprint $table) {
                    $table->dropIndex(['promoted_from_batch_id']);
                });
            } catch (\Throwable $e) {
                // Ignore if index does not exist in this environment.
            }

            // Convert integer representation to ULID (fallback or null if already corrupted)
            // But since no proper data could be stored in a bigInt to represent a ULID anyway,
            // the data is effectively lost, padding/converting isn't strictly necessary.
            
            Schema::table('lock_lists', function (Blueprint $table) {
                $table->dropColumn('promoted_from_batch_id');
            });

            Schema::table('lock_lists', function (Blueprint $table) {
                $table->renameColumn('promoted_from_batch_id_new', 'promoted_from_batch_id');
            });

            Schema::table('lock_lists', function (Blueprint $table) {
                $table->index('promoted_from_batch_id');
            });
        }

        // 2. Change member_email to text in reconciliation_queue to support Laravel encryption
        if (Schema::hasTable('reconciliation_queue') && Schema::hasColumn('reconciliation_queue', 'member_email')) {
            Schema::table('reconciliation_queue', function (Blueprint $table) {
                $table->text('member_email_new')->nullable()->after('member_email');
            });

            DB::table('reconciliation_queue')->update([
                'member_email_new' => DB::raw('member_email'),
            ]);

            Schema::table('reconciliation_queue', function (Blueprint $table) {
                $table->dropColumn('member_email');
            });

            Schema::table('reconciliation_queue', function (Blueprint $table) {
                $table->renameColumn('member_email_new', 'member_email');
            });
        }
    }

    public function down(): void
    {
        // We will not reverse these type changes as they are critical fixes and harmless.
    }
};
