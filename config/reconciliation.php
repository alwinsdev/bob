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
     * historical "Home Open / Home Close" flag in the previous Final BOB run.
     * This enables mid-term corrections for records that weren't formally
     * flagged but require manual agent/payee correction.
     *
     * When false: Only records that were explicitly flagged (Home Open / Home Close)
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

];
