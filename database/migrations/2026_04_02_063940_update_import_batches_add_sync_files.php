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
        Schema::table('import_batches', function (Blueprint $table) {
            $table->enum('sync_strategy', ['ims', 'health_sherpa'])->default('ims')->after('status');
            $table->string('payee_file_path')->nullable()->after('ims_original_name');
            $table->string('payee_original_name')->nullable()->after('payee_file_path');
            $table->string('health_sherpa_file_path')->nullable()->after('payee_original_name');
            $table->string('health_sherpa_original_name')->nullable()->after('health_sherpa_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'sync_strategy',
                'payee_file_path',
                'payee_original_name',
                'health_sherpa_file_path',
                'health_sherpa_original_name'
            ]);
        });
    }
};
