<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add enterprise retry lineage fields:
     * - retry_of_batch_id: direct source run for this attempt
     * - retry_group_id: root run id across the full attempt chain
     * - attempt_no: ordinal attempt number in the chain
     * - retry_reason: operator-entered rerun reason/context
     */
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->string('retry_of_batch_id', 26)->nullable()->after('previous_batch_id');
            $table->string('retry_group_id', 26)->nullable()->after('retry_of_batch_id');
            $table->unsignedSmallInteger('attempt_no')->default(1)->after('retry_group_id');
            $table->text('retry_reason')->nullable()->after('attempt_no');

            $table->index('retry_of_batch_id', 'import_batches_retry_of_batch_id_index');
            $table->index('retry_group_id', 'import_batches_retry_group_id_index');
            $table->index(['retry_group_id', 'attempt_no'], 'import_batches_retry_group_attempt_index');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropIndex('import_batches_retry_group_attempt_index');
            $table->dropIndex('import_batches_retry_group_id_index');
            $table->dropIndex('import_batches_retry_of_batch_id_index');
            $table->dropColumn(['retry_of_batch_id', 'retry_group_id', 'attempt_no', 'retry_reason']);
        });
    }
};
