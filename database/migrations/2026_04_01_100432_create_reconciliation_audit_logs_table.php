<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('transaction_id')->index();
            $table->enum('action', ['resolved', 'skipped', 'flagged', 'lock_acquired', 'lock_released']);
            $table->json('previous_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('previous_agent_code')->nullable();
            $table->string('new_agent_code')->nullable();
            $table->foreignId('modified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_audit_logs');
    }
};
