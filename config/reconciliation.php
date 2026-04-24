<?php

/**
 * Reconciliation Module Configuration
 *
 * Controls the enterprise-level behavior of the Contract Patch Engine
 * and other reconciliation processing parameters.
 */
return [

    // ── Contract Patch Engine ────────────────────────────────────────────────

    /**
     * ALLOW_FORCE_PATCH
     *
     * When true: Contract Patch will update records even if they have no
     * historical "House Open / House Close" flag in the previous Final BOB run.
     * This enables mid-term corrections for records that weren't formally
     * flagged but require manual agent/payee correction.
     *
     * When false: Only records that were explicitly flagged (House Open / House Close)
     * in the previous run will be eligible for patching, enforcing strictest
     * data integrity.
     *
     * Recommended: false in enterprise/compliance mode.
     *              true only for controlled exception windows.
     */
    'allow_force_patch' => (bool) env('RECON_ALLOW_FORCE_PATCH', false),

    /**
     * PATCH_CHUNK_SIZE
     *
     * Number of DB row updates and audit entries to buffer before flushing
     * to the database. Higher values = fewer round trips (faster), but more
     * memory usage. 500 is a solid default for typical cloud DB setups.
     */
    'patch_chunk_size' => (int) env('RECON_PATCH_CHUNK_SIZE', 500),

    /**
     * PATCH_REALTIME_FLUSH_INTERVAL
     *
     * How often (in rows) to flush live progress to the import_batches table
     * for real-time UI monitoring. Lower = more responsive UI, higher =
     * fewer DB writes.
     */
    'patch_realtime_flush_interval' => (int) env('RECON_PATCH_FLUSH_INTERVAL', 50),

    // ── Record Locking ───────────────────────────────────────────────────────

    /**
     * LOCK_TIMEOUT_MINUTES
     *
     * How long (in minutes) an analyst's record lock is considered valid.
     * After this duration (+1 minute clock-skew grace), another user may
     * acquire the lock. Applies to both the acquire() check and the
     * ReleaseExpiredLocksJob scheduled cleanup.
     */
    'lock_timeout_minutes' => (int) env('RECON_LOCK_TIMEOUT_MINUTES', 30),

    // ── Bulk Operations ──────────────────────────────────────────────────────

    /**
     * BULK_RESOLVE_MAX
     *
     * Maximum number of records that can be resolved in a single bulk-resolve
     * operation. Keeping this low limits transaction size, lock contention,
     * and the potential impact of a DoS-style bulk request.
     */
    'bulk_resolve_max' => (int) env('RECON_BULK_RESOLVE_MAX', 100),

    // ── System User ──────────────────────────────────────────────────────────

    /**
     * SYSTEM_USER_ID
     *
     * Fallback user ID used when an action is triggered by a background job
     * rather than an authenticated human user. Must reference a valid users.id.
     * Verified at boot in AppServiceProvider.
     */
    'system_user_id' => (int) env('RECON_SYSTEM_USER_ID', 1),

    // ── ETL Engine ───────────────────────────────────────────────────────────

    /**
     * AUTO_REVIEW_THRESHOLD
     *
     * Match confidence score (0–100) at or above which the ETL engine
     * automatically marks a record as matched without analyst review.
     * Configurable per deployment to tune false-positive tolerance.
     */
    'auto_review_threshold' => (float) env('RECON_AUTO_REVIEW_THRESHOLD', 90.0),

    /**
     * CONTRACT_PATCH_FLAG_VALUES
     *
     * The allowed flag values for contract patch eligibility.
     * Only records with these flag values in the previous run are eligible
     * for patching (when allow_force_patch is false).
     */
    'contract_patch_flag_values' => ['House Open', 'House Close'],

];

