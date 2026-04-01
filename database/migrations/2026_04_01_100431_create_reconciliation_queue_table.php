<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_queue', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('transaction_id')->unique();
            $table->foreignUlid('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();

            // BOB/Carrier fields (immutable)
            $table->string('carrier')->nullable();
            $table->string('contract_id')->nullable();
            $table->string('product')->nullable();
            $table->string('member_first_name')->nullable();
            $table->string('member_last_name')->nullable();
            $table->text('member_dob')->nullable(); // encrypted
            $table->string('member_email')->nullable();
            $table->text('member_phone')->nullable(); // encrypted
            $table->date('effective_date')->nullable();

            // IMS matched fields
            $table->string('ims_transaction_id')->nullable();
            $table->string('client_first_name')->nullable();
            $table->string('client_last_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('agent_id')->nullable();
            $table->string('agent_first_name')->nullable();

            // Match results
            $table->decimal('match_confidence', 5, 2)->nullable();
            $table->string('match_method')->nullable();
            $table->json('field_scores')->nullable();

            // Status
            $table->enum('status', ['pending', 'matched', 'resolved', 'flagged', 'skipped']);

            // Resolution (Column C)
            $table->string('aligned_agent_code')->nullable();
            $table->string('aligned_agent_name')->nullable();
            $table->string('group_team_sales')->nullable();
            $table->string('payee_name')->nullable();
            $table->enum('compensation_type', ['New', 'Renewal'])->nullable();

            // Record Locking & Resolution meta
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('match_confidence');
            $table->index('aligned_agent_code');
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_queue');
    }
};
