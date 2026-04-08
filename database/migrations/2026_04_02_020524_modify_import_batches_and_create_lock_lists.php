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
            $table->dropColumn(['file_name', 'original_name', 'type']);
            $table->string('carrier_file_path')->after('id')->nullable();
            $table->string('carrier_original_name')->after('carrier_file_path')->nullable();
            $table->string('ims_file_path')->after('carrier_original_name')->nullable();
            $table->string('ims_original_name')->after('ims_file_path')->nullable();
        });

        Schema::create('lock_lists', function (Blueprint $table) {
            $table->id();
            $table->string('policy_id')->unique()->index();
            $table->string('agent_name')->nullable();
            $table->string('department')->nullable();
            $table->string('payee_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lock_lists');

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn(['carrier_file_path', 'carrier_original_name', 'ims_file_path', 'ims_original_name']);
            $table->string('file_name')->nullable();
            $table->string('original_name')->nullable();
            $table->string('type')->nullable();
        });
    }
};
