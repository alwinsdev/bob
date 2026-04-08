<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add batch_type + contract file columns to support the Contract Patch workflow.
     * batch_type: 'standard' (weekly sync) | 'contract_patch' (mid-week contract file run)
     */
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->string('batch_type')->default('standard')->after('id');
            $table->string('contract_file_path')->nullable()->after('health_sherpa_original_name');
            $table->string('contract_original_name')->nullable()->after('contract_file_path');
            $table->unsignedInteger('contract_patched_records')->default(0)->after('contract_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'batch_type',
                'contract_file_path',
                'contract_original_name',
                'contract_patched_records',
            ]);
        });
    }
};
