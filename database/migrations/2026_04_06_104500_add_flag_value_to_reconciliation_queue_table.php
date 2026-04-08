<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            $table->string('flag_value')->nullable()->after('status');
            $table->index('flag_value');
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            $table->dropIndex(['flag_value']);
            $table->dropColumn('flag_value');
        });
    }
};
