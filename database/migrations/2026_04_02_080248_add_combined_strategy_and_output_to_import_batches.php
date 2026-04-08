<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update enum only on MySQL-compatible engines.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // 1. Modify the sync_strategy enum to allow 'combined'
            DB::statement("ALTER TABLE import_batches MODIFY sync_strategy ENUM('ims','health_sherpa','combined') NOT NULL DEFAULT 'combined'");
        }

        Schema::table('import_batches', function (Blueprint $table) {
            // 2. Add output Excel file path for download
            $table->string('output_file_path')->nullable()->after('duplicate_strategy');

            // 3. Add separate IMS & HS match counters for the split pipeline UI
            $table->unsignedInteger('ims_matched_records')->default(0)->after('output_file_path');
            $table->unsignedInteger('hs_matched_records')->default(0)->after('ims_matched_records');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn(['output_file_path', 'ims_matched_records', 'hs_matched_records']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE import_batches MODIFY sync_strategy ENUM('ims','health_sherpa') NOT NULL");
        }
    }
};
