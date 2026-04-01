<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_row_errors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('import_batch_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->json('raw_data');
            $table->enum('error_type', ['validation', 'duplicate', 'parse_error', 'system_error']);
            $table->json('error_messages');
            $table->string('field_name')->nullable();
            $table->boolean('is_retryable')->default(false);
            $table->timestamp('retried_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_row_errors');
    }
};
