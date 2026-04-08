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
        Schema::table('reconciliation_audit_logs', function (Blueprint $table) {
            $table->string('action')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_audit_logs', function (Blueprint $table) {
            // Reverting to enum if strictly necessary, but standard string is safer
            // We just leave it as string in down or drop if needed, but best to revert
            // Note: Doctrine DBAL required to actually reverse to enum easily.
            $table->string('action')->change();
        });
    }
};
