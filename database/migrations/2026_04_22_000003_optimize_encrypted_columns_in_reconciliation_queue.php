<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optimize encrypted column types: TEXT → VARCHAR(255).
 *
 * Rationale:
 *  - `member_dob` and `member_phone` are stored AES-256-CBC encrypted via
 *    Laravel's Crypt facade. The encrypted ciphertext for a short value
 *    (e.g. a 10-char date or 15-char phone) is approximately 100–140 chars
 *    in base64 — well within VARCHAR(255).
 *
 *  - TEXT fields consume more storage overhead per row and cannot benefit
 *    from MySQL's row-format optimizations that apply to VARCHAR columns.
 *
 *  - TEXT fields also inadvertently hint at full-text indexing support,
 *    which is meaningless for encrypted ciphertext.
 *
 * Safety:
 *  - MySQL/MariaDB truncates TEXT → VARCHAR(255) only if the stored value
 *    exceeds 255 chars. AES-256-CBC + base64 of a date/phone is always < 255.
 *  - The migration is additive (alters column definition, no data is dropped).
 *  - The `doctrine/dbal` package is required by Laravel for ->change() on MySQL.
 *    If not installed: composer require doctrine/dbal
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            // Encrypted date of birth — ciphertext is fixed-size, ~120 chars
            $table->string('member_dob', 255)->nullable()->change();

            // Encrypted phone number — ciphertext is fixed-size, ~120 chars
            $table->string('member_phone', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_queue', function (Blueprint $table) {
            // Revert to TEXT if rolling back (no data loss — TEXT holds more)
            $table->text('member_dob')->nullable()->change();
            $table->text('member_phone')->nullable()->change();
        });
    }
};
