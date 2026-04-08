<?php

namespace App\Services\Reconciliation\ETL;

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
            $department = $this->normalizer->string($row['DEPARTMENT_NAME'] ?? $row['AGENT_NAME'] ?? '');

            if ($department !== '') {
                $state->payeeMap[$department] = $row['PAYEE_NAME'] ?? null;
            }
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
                'agent_name' => $row['AGENT'] ?? $row['AGENT_NAME'] ?? null,
                'effective_date_ts' => $effectiveDate !== '' ? strtotime($effectiveDate) : null,
                'source' => 'health_sherpa',
            ];

            if ($email !== '') {
                $state->hsByEmail[$email] = $record;
            }

            if ($phone !== '' && $effectiveDate !== '') {
                $state->hsByPhoneDate[$phone][] = $record;
            }
        });
    }

    public function getLockListEntry(string $policyId, ReconciliationLookupState $state): ?array
    {
        if ($policyId === '') {
            return null;
        }

        if (array_key_exists($policyId, $state->lockList)) {
            return $state->lockList[$policyId];
        }

        if (isset($state->lockListMisses[$policyId])) {
            return null;
        }

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
