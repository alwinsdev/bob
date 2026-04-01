<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('file_name');
            $table->string('original_name');
            $table->enum('type', ['carrier', 'ims']);
            $table->integer('total_records')->nullable();
            $table->integer('processed_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->integer('skipped_duplicates')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'completed_with_errors', 'failed']);
            $table->text('error_message')->nullable();
            $table->enum('duplicate_strategy', ['skip', 'update'])->default('skip');
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
