<?php

namespace App\Services\Reconciliation\ETL;

use App\Models\ImportBatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReconciliationRecordResolver
{
    public function __construct(
        private readonly ReconciliationValueNormalizer $normalizer,
        private readonly ReconciliationLookupBuilder $lookupBuilder
    ) {}

    public function resolve(array $row, ImportBatch $batch, ReconciliationLookupState $state): array
    {
        $contractId = trim((string) ($row['CONTRACT_ID'] ?? $row['Contract ID'] ?? $row['Policy ID'] ?? null));

        if ($contractId === '') {
            throw new \Exception('Missing Contract ID / Policy ID in BOB row.');
        }

        $email = $this->normalizer->string($row['MEMBER_EMAIL_ADDRESS'] ?? $row['Member Email'] ?? $row['EMAIL'] ?? '');
        $phone = $this->normalizer->phone($row['MEMBER_PHONE_NUMBER'] ?? $row['Member Phone'] ?? $row['PHONE'] ?? '');
        $firstName = $this->normalizer->string($row['MEMBER_FIRST_NAME'] ?? $row['Member First Name'] ?? '');
        $lastName = $this->normalizer->string($row['MEMBER_LAST_NAME'] ?? $row['Member Last Name'] ?? '');
        $dob = $this->normalizer->date($row['MEMBER_DOB'] ?? $row['Member DOB'] ?? '');
        $effectiveDate = $this->normalizer->date($row['COVERAGE_EFFECTIVE_DATE'] ?? $row['Effective Date'] ?? $row['EFFECTIVE_DATE'] ?? '');

        $imsResult = $this->runIMSFlow($state, $email, $phone, $firstName, $lastName, $dob);
        $healthSherpaResult = $this->runHSFlow($state, $email, $phone, $effectiveDate);

        $status = 'pending';
        $matchMethods = [];
        $matchedSources = [];

        $transactionId = null;
        $agentId = null;
        $agentName = null;
        $department = null;
        $payeeName = null;

        if ($imsResult !== null) {
            $matchMethods[] = $imsResult['method'];
            $matchedSources[] = 'ims';
            $status = 'matched';

            $matchedRecord = $imsResult['record'];
            $transactionId = $matchedRecord['transaction_id'] ?? null;
            $agentId = $matchedRecord['agent_id'] ?? null;
            $agentName = $matchedRecord['agent_name'] ?? null;
            $department = $matchedRecord['department'] ?? null;

            if ($department) {
                $payeeName = $state->payeeMap[$this->normalizer->string((string) $department)] ?? null;
            }
        }

        if ($healthSherpaResult !== null) {
            $matchMethods[] = $healthSherpaResult['method'];
            $matchedSources[] = 'health_sherpa';
            $status = 'matched';

            $transactionId = $transactionId ?: ($healthSherpaResult['record']['transaction_id'] ?? null);
            $agentName = $agentName ?: ($healthSherpaResult['record']['agent_name'] ?? null);
        }

        $matchMethod = empty($matchMethods) ? null : implode(' + ', $matchMethods);
        $originalAgentName = $agentName;
        $originalMatchMethod = $matchMethod;

        $wasOverridden = false;
        $lockListEntry = $this->lookupBuilder->getLockListEntry($contractId, $state);

        if ($lockListEntry !== null) {
            $agentName = $lockListEntry['agent_name'] ?? $agentName;
            $department = $lockListEntry['department'] ?? $department;
            $payeeName = $lockListEntry['payee_name'] ?? $payeeName;
            $matchMethod = 'LockList Override';
            $status = 'resolved';

            if (empty($matchedSources)) {
                $matchedSources[] = 'locklist';
            }

            $wasOverridden = true;

            Log::info("[ETL][Locklist] Contract {$contractId} overridden by Locklist.", [
                'batch' => $batch->id,
                'agent_name' => $agentName,
                'department' => $department,
                'payee' => $payeeName,
            ]);
        }

        if ($status === 'pending') {
            Log::warning("[ETL][Unmatched] Contract {$contractId} not resolved by IMS, HS, or Locklist.", [
                'batch' => $batch->id,
                'email' => $email !== '' ? $email : 'N/A',
                'phone' => $phone !== '' ? $phone : 'N/A',
                'name' => "{$firstName} {$lastName}",
            ]);
        }

        $rawEmail = $row['MEMBER_EMAIL_ADDRESS'] ?? $row['Member Email'] ?? $row['EMAIL'] ?? null;
        $record = [
            'id' => (string) Str::ulid(),
            'transaction_id' => 'TXN-'.strtoupper(Str::random(10)),
            'import_batch_id' => $batch->id,
            'carrier' => $row['Carrier'] ?? null,
            'contract_id' => $contractId,
            'product' => $row['PRODUCT'] ?? $row['Product'] ?? null,
            'member_first_name' => $row['MEMBER_FIRST_NAME'] ?? $row['Member First Name'] ?? null,
            'member_last_name' => $row['MEMBER_LAST_NAME'] ?? $row['Member Last Name'] ?? null,
            'member_dob' => $dob !== '' ? encrypt($dob) : null,
            'member_email' => $rawEmail ? encrypt($rawEmail) : null,
            'member_phone' => $phone !== '' ? encrypt($phone) : null,
            'effective_date' => $effectiveDate !== '' ? $effectiveDate : null,
            'ims_transaction_id' => $transactionId,
            'agent_id' => $agentId,
            'agent_first_name' => trim((string) ($agentName ?? '')),
            'match_method' => $matchMethod,
            'match_confidence' => in_array($status, ['matched', 'resolved'], true) ? 100.00 : null,
            'status' => $status,
            'override_flag' => $wasOverridden,
            'override_source' => $wasOverridden ? 'lock_list' : null,
            'original_agent_name' => trim((string) ($originalAgentName ?? '')),
            'original_match_method' => $originalMatchMethod,
            'aligned_agent_code' => $agentId,
            'aligned_agent_name' => trim((string) ($agentName ?? '')),
            'group_team_sales' => trim((string) ($department ?? '')),
            'payee_name' => trim((string) ($payeeName ?? '')),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return [$record, $matchedSources, $wasOverridden];
    }

    private function runIMSFlow(
        ReconciliationLookupState $state,
        string $email,
        string $phone,
        string $firstName,
        string $lastName,
        string $dob
    ): ?array {
        if ($email !== '' && isset($state->imsByEmail[$email])) {
            return ['record' => $state->imsByEmail[$email], 'method' => 'IMS:Email'];
        }

        if ($phone !== '' && isset($state->imsByPhone[$phone])) {
            return ['record' => $state->imsByPhone[$phone], 'method' => 'IMS:Phone'];
        }

        if ($firstName !== '' && $lastName !== '' && isset($state->imsByFirstLast["{$firstName}_{$lastName}"])) {
            return ['record' => $state->imsByFirstLast["{$firstName}_{$lastName}"], 'method' => 'IMS:FirstLastName'];
        }

        if ($dob !== '' && $lastName !== '' && isset($state->imsByDobLast["{$dob}_{$lastName}"])) {
            return ['record' => $state->imsByDobLast["{$dob}_{$lastName}"], 'method' => 'IMS:DOB+LastName'];
        }

        return null;
    }

    private function runHSFlow(
        ReconciliationLookupState $state,
        string $email,
        string $phone,
        string $effectiveDate
    ): ?array {
        if ($email !== '' && isset($state->hsByEmail[$email])) {
            return ['record' => $state->hsByEmail[$email], 'method' => 'HS:Email'];
        }

        $effectiveTimestamp = $effectiveDate !== '' ? strtotime($effectiveDate) : null;

        if ($phone !== '' && $effectiveTimestamp && isset($state->hsByPhoneDate[$phone])) {
            foreach ($state->hsByPhoneDate[$phone] as $record) {
                if (! empty($record['effective_date_ts'])) {
                    $differenceInDays = abs($effectiveTimestamp - $record['effective_date_ts']) / 86400;

                    if ($differenceInDays <= 30) {
                        return ['record' => $record, 'method' => 'HS:Phone+Date(±30d)'];
                    }
                }
            }
        }

        return null;
    }
}
