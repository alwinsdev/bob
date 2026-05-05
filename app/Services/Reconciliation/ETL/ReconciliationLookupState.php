<?php

namespace App\Services\Reconciliation\ETL;

class ReconciliationLookupState
{
    // ── IMS identity hashmaps (built from parent batch IMS file) ──────────
    public array $imsByEmail = [];

    public array $imsByPhone = [];

    public array $imsByFirstLast = [];

    public array $imsByDobLast = [];

    // ── Health Sherpa identity hashmaps ──────────────────────────────────
    public array $hsByEmail = [];

    public array $hsByPhoneDate = [];

    /** Phone-only HS fallback (first hit wins) — used when EffectiveDate absent. */
    public array $hsByPhone = [];

    // ── Payee Map (Department / Agent Name → Payee Name) ─────────────────
    public array $payeeMap = [];

    // ── Lock List (lazy-loaded by policy id) ──────────────────────────────
    public array $lockList = [];

    public array $lockListMisses = [];

    // ── Carrier BOB identity hashmaps (analysis only) ─────────────────────
    /** Contract ID → carrier identity record (last-resort fallback). */
    public array $carrierByContract = [];

    public array $carrierByEmail = [];

    public array $carrierByPhone = [];

    public array $carrierByFirstLast = [];

    public array $carrierByDobLast = [];

    // ── Final BOB (resolved reconciliation_queue) — analysis only ─────────
    /** Contract ID → resolved record { payee_name, agent_name, agent_code, department, match_method, status }. */
    public array $finalBobByContract = [];
}
