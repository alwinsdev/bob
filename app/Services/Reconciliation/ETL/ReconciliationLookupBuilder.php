<?php

namespace App\Services\Reconciliation\ETL;

use App\Models\ReconciliationQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelReader;

class ReconciliationLookupBuilder
{
    public function __construct(
        private readonly ReconciliationValueNormalizer $normalizer
    ) {}

    public function buildPayeeMap(string $filePath, ReconciliationLookupState $state): void
    {
        SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows()->each(function (array $row) use ($state) {
            $payee = trim((string) ($row['PAYEE_NAME'] ?? $row['Payee Name'] ?? $row['payee_name'] ?? ''));

            if ($payee === '') {
                return;
            }

            // Index under both Department and Agent name keys so the cascade
            // can resolve via either pivot (per spec: "Department Name OR Agent Name").
            $department = $this->normalizer->string($row['DEPARTMENT_NAME'] ?? $row['DEPARTMENT'] ?? $row['Department'] ?? '');
            $agentName  = $this->normalizer->string($row['AGENT_NAME'] ?? $row['Agent Name'] ?? '');

            if ($department !== '') {
                $state->payeeMap[$department] = $payee;
            }
            if ($agentName !== '' && !isset($state->payeeMap[$agentName])) {
                $state->payeeMap[$agentName] = $payee;
            }
        });
    }

    /**
     * Build identity-keyed Carrier BOB hashmaps for the Payee Back-Flow Analysis.
     *
     * The standard ETL doesn't need these (it streams Carrier as the input).
     * For the analysis engine, we treat Carrier as the LAST RESORT identity
     * fallback (Source ① in the reverse-order cascade) and need O(1) lookup
     * by Email / Phone / Name / DOB+LastName.
     */
    public function buildCarrierAnalysisMap(string $filePath, ReconciliationLookupState $state): void
    {
        SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows()->each(function (array $row) use ($state) {
            $contractId = $this->normalizer->patchId($row['CONTRACT_ID'] ?? $row['POLICY_ID'] ?? $row['Contract ID'] ?? $row['Policy ID'] ?? '');
            $email      = $this->normalizer->string($row['MEMBER_EMAIL'] ?? $row['EMAIL'] ?? $row['CLIENT_EMAIL'] ?? '');
            $phone      = $this->normalizer->phone($row['MEMBER_PHONE'] ?? $row['PHONE'] ?? $row['CLIENT_PHONE'] ?? $row['MOBILE'] ?? '');
            $firstName  = $this->normalizer->string($row['MEMBER_FIRST_NAME'] ?? $row['FIRST_NAME'] ?? $row['CLIENT_FIRST_NAME'] ?? '');
            $lastName   = $this->normalizer->string($row['MEMBER_LAST_NAME'] ?? $row['LAST_NAME'] ?? $row['CLIENT_LAST_NAME'] ?? '');
            $dob        = $this->normalizer->date($row['MEMBER_DOB'] ?? $row['DOB'] ?? $row['CLIENT_DOB'] ?? '');

            $record = [
                'contract_id' => $contractId,
                'first_name'  => $firstName,
                'last_name'   => $lastName,
                'email'       => $email,
                'phone'       => $phone,
                'agent_name'  => trim((string) ($row['AGENT_NAME'] ?? $row['Agent Name'] ?? '')),
                'agent_code'  => trim((string) ($row['AGENT_CODE'] ?? $row['Agent Code'] ?? '')),
                'department'  => trim((string) ($row['DEPARTMENT'] ?? $row['Department'] ?? $row['GROUP_TEAM_SALES'] ?? '')),
                'payee_name'  => trim((string) ($row['PAYEE_NAME'] ?? $row['Payee Name'] ?? '')),
                'source'      => 'carrier_bob',
            ];

            if ($contractId !== '' && !isset($state->carrierByContract[strtolower($contractId)])) {
                $state->carrierByContract[strtolower($contractId)] = $record;
            }
            if ($email !== '' && !isset($state->carrierByEmail[$email])) {
                $state->carrierByEmail[$email] = $record;
            }
            if ($phone !== '' && !isset($state->carrierByPhone[$phone])) {
                $state->carrierByPhone[$phone] = $record;
            }
            if ($firstName !== '' && $lastName !== '' && !isset($state->carrierByFirstLast["{$firstName}_{$lastName}"])) {
                $state->carrierByFirstLast["{$firstName}_{$lastName}"] = $record;
            }
            if ($dob !== '' && $lastName !== '' && !isset($state->carrierByDobLast["{$dob}_{$lastName}"])) {
                $state->carrierByDobLast["{$dob}_{$lastName}"] = $record;
            }
        });
    }

    /**
     * Build the Final BOB hashmap (Source ⑤ — Source of Truth) keyed by Contract ID,
     * sourced from the resolved reconciliation_queue rows of the parent batch.
     *
     * O(N) — single SELECT, materialized into RAM as a hash. Streams cleanly for 100K+ rows.
     */
    public function buildFinalBobAnalysisMap(string $parentBatchId, ReconciliationLookupState $state): void
    {
        ReconciliationQueue::query()
            ->where('import_batch_id', $parentBatchId)
            ->select(['contract_id', 'payee_name', 'match_method', 'status', 'aligned_agent_name', 'aligned_agent_code', 'group_team_sales'])
            ->cursor()
            ->each(function (ReconciliationQueue $r) use ($state) {
                $key = strtolower(trim((string) $r->contract_id));
                if ($key === '' || isset($state->finalBobByContract[$key])) {
                    return;
                }
                $state->finalBobByContract[$key] = [
                    'payee_name'   => (string) ($r->payee_name ?? ''),
                    'agent_name'   => (string) ($r->aligned_agent_name ?? ''),
                    'agent_code'   => (string) ($r->aligned_agent_code ?? ''),
                    'department'   => (string) ($r->group_team_sales ?? ''),
                    'match_method' => (string) ($r->match_method ?? ''),
                    'status'       => (string) ($r->status ?? ''),
                ];
            });
    }

    public function buildIMSMap(string $filePath, ReconciliationLookupState $state): void
    {
        SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows()->each(function (array $row) use ($state) {
            $departmentValue = $row['DEPARTMENT_NAME'] ?? $row['AGENT_NAME'] ?? '';
            $department = $this->normalizer->string($departmentValue);

            if ($department === '') {
                Log::debug('[ETL][IMS] Row skipped - missing Department Name.', [
                    'agent' => $row['AGENT_ID'] ?? 'N/A',
                ]);

                return;
            }

            $email = $this->normalizer->string($row['CLIENT_EMAIL'] ?? '');
            $phone = $this->normalizer->phone($row['CLIENT_PHONE'] ?? '');
            $firstName = $this->normalizer->string($row['SUBMITTER_FIRST_NAME'] ?? $row['CLIENT_FIRST_NAME'] ?? '');
            $lastName = $this->normalizer->string($row['SUBMITTER_LAST_NAME'] ?? $row['CLIENT_LAST_NAME'] ?? '');
            $dob = $this->normalizer->date($row['CLIENT_DOB'] ?? '');
            $agentName = trim((string) ($row['AGENT_NAME'] ?? ''));

            if ($agentName === '') {
                $agentName = trim(($row['AGENT_FIRST_NAME'] ?? '').' '.($row['AGENT_LAST_NAME'] ?? ''));
            }

            $record = [
                'transaction_id' => $row['TRANSACTION_ID'] ?? $row['IMS_TRANSACTION_ID'] ?? null,
                'agent_id' => $row['AGENT_ID'] ?? $row['AGENT_CODE'] ?? null,
                'agent_name' => $agentName,
                'department' => $departmentValue,
                'payee_name' => trim((string) ($row['PAYEE_NAME'] ?? $row['Payee Name'] ?? $row['payee_name'] ?? '')),
                'contract_id' => trim((string) ($row['POLICY_ID'] ?? $row['CONTRACT_ID'] ?? $row['Policy ID'] ?? $row['Contract ID'] ?? '')),
                'source' => 'ims',
            ];

            if ($email !== '') {
                $state->imsByEmail[$email] = $record;
            }

            if ($phone !== '') {
                $state->imsByPhone[$phone] = $record;
            }

            if ($firstName !== '' && $lastName !== '') {
                $state->imsByFirstLast["{$firstName}_{$lastName}"] = $record;
            }

            if ($dob !== '' && $lastName !== '') {
                $state->imsByDobLast["{$dob}_{$lastName}"] = $record;
            }
        });
    }

    public function buildHealthSherpaMap(string $filePath, ReconciliationLookupState $state): void
    {
        SimpleExcelReader::create($filePath)->headerOnRow(0)->getRows()->each(function (array $row) use ($state) {
            $email = $this->normalizer->string($row['EMAIL'] ?? '');
            $phone = $this->normalizer->phone($row['PHONE'] ?? '');
            $effectiveDate = $this->normalizer->date($row['EFFECTIVE_DATE'] ?? '');

            $record = [
                'transaction_id' => $row['FFM_APP_ID'] ?? $row['IMS_TRANSACTION_ID'] ?? null,
                'agent_name' => trim((string) ($row['AGENT'] ?? $row['AGENT_NAME'] ?? '')),
                'agent_id' => trim((string) ($row['AGENT_CODE'] ?? $row['AGENT_ID'] ?? '')),
                'payee_name' => trim((string) ($row['PAYEE_NAME'] ?? $row['Payee Name'] ?? $row['payee_name'] ?? '')),
                'department' => trim((string) ($row['DEPARTMENT'] ?? $row['Department'] ?? '')),
                'contract_id' => trim((string) ($row['CONTRACT_ID'] ?? $row['POLICY_ID'] ?? $row['Contract ID'] ?? $row['Policy ID'] ?? '')),
                'effective_date_ts' => $effectiveDate !== '' ? strtotime($effectiveDate) : null,
                'source' => 'health_sherpa',
            ];

            if ($email !== '') {
                $state->hsByEmail[$email] = $record;
            }

            if ($phone !== '' && $effectiveDate !== '') {
                $state->hsByPhoneDate[$phone][] = $record;
            }

            // Phone-only fallback (first hit wins) — used when EffectiveDate is absent
            // from the missing-payee file. Keeps cascade resilient on minimal inputs.
            if ($phone !== '' && !isset($state->hsByPhone[$phone])) {
                $state->hsByPhone[$phone] = $record;
            }
        });
    }

    /**
     * Preload the entire lock_lists table into memory once before streaming.
     *
     * Previous behavior: getLockListEntry() lazy-loaded one row at a time
     * via DB::first() on every uncached contract id during carrier streaming.
     * For a 50K-row batch with 80% unique policy_ids that's ~40K DB round-trips
     * (~120s of wall-clock blocking), turning a CPU-bound stream into an
     * I/O-bound bottleneck. lock_lists is small and relatively static —
     * one cursor pass dominates per-batch.
     *
     * Call this from ReconciliationETLService::processBatch() and
     * processContractPatch() AFTER constructing $this->lookupState and
     * BEFORE the carrier/contract streaming pass.
     */
    public function preloadLockList(ReconciliationLookupState $state): void
    {
        $state->lockList = [];
        $state->lockListMisses = [];

        DB::table('lock_lists')
            ->select('policy_id', 'agent_name', 'department', 'payee_name')
            ->cursor()
            ->each(function ($row) use ($state) {
                $key = (string) $row->policy_id;
                if ($key === '') {
                    return;
                }
                $state->lockList[$key] = [
                    'agent_name' => $row->agent_name,
                    'department' => $row->department,
                    'payee_name' => $row->payee_name,
                ];
            });
    }

    public function getLockListEntry(string $policyId, ReconciliationLookupState $state): ?array
    {
        if ($policyId === '') {
            return null;
        }

        // Fast path: preloaded map populated by preloadLockList().
        if (array_key_exists($policyId, $state->lockList)) {
            return $state->lockList[$policyId];
        }

        if (isset($state->lockListMisses[$policyId])) {
            return null;
        }

        // Fallback: lazy-load (kept for callers that skip preloading,
        // and as a defensive net for new lock_list rows added mid-batch).
        // Cached on miss so we don't hit DB twice for the same id.
        $row = DB::table('lock_lists')
            ->select('agent_name', 'department', 'payee_name')
            ->where('policy_id', $policyId)
            ->first();

        if (! $row) {
            $state->lockListMisses[$policyId] = true;

            return null;
        }

        $entry = [
            'agent_name' => $row->agent_name,
            'department' => $row->department,
            'payee_name' => $row->payee_name,
        ];

        $state->lockList[$policyId] = $entry;

        return $entry;
    }
}
