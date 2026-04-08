<?php

namespace Tests\Unit\Reconciliation\ETL;

use App\Models\ImportBatch;
use App\Services\Reconciliation\ETL\ReconciliationLookupState;
use App\Services\Reconciliation\ETL\ReconciliationRecordResolver;
use Tests\TestCase;

class ReconciliationRecordResolverTest extends TestCase
{
    public function test_ims_email_match_populates_assignment_and_payee_details(): void
    {
        $resolver = app(ReconciliationRecordResolver::class);
        $state = new ReconciliationLookupState;
        $batch = new ImportBatch;
        $batch->id = 'batch-100';

        $state->imsByEmail['member@example.com'] = [
            'transaction_id' => 'IMS-123',
            'agent_id' => 'AG-100',
            'agent_name' => 'Jamie Analyst',
            'department' => 'Team Alpha',
        ];
        $state->payeeMap['team alpha'] = 'Payee Alpha';
        $state->lockListMisses['CONTRACT-100'] = true;

        [$record, $matchedSources, $wasOverridden] = $resolver->resolve([
            'CONTRACT_ID' => 'CONTRACT-100',
            'MEMBER_EMAIL_ADDRESS' => 'member@example.com',
            'MEMBER_FIRST_NAME' => 'Casey',
            'MEMBER_LAST_NAME' => 'Jones',
            'MEMBER_DOB' => '1990-01-02',
        ], $batch, $state);

        $this->assertSame(['ims'], $matchedSources);
        $this->assertFalse($wasOverridden);
        $this->assertSame('matched', $record['status']);
        $this->assertSame('IMS:Email', $record['match_method']);
        $this->assertSame('AG-100', $record['aligned_agent_code']);
        $this->assertSame('Jamie Analyst', $record['aligned_agent_name']);
        $this->assertSame('Team Alpha', $record['group_team_sales']);
        $this->assertSame('Payee Alpha', $record['payee_name']);
        $this->assertSame('member@example.com', decrypt($record['member_email']));
    }

    public function test_health_sherpa_phone_and_date_match_is_used_as_fallback(): void
    {
        $resolver = app(ReconciliationRecordResolver::class);
        $state = new ReconciliationLookupState;
        $batch = new ImportBatch;
        $batch->id = 'batch-200';

        $state->hsByPhoneDate['5551234567'] = [[
            'transaction_id' => 'HS-200',
            'agent_name' => 'Taylor Sherpa',
            'effective_date_ts' => strtotime('2026-05-01'),
        ]];
        $state->lockListMisses['CONTRACT-200'] = true;

        [$record, $matchedSources, $wasOverridden] = $resolver->resolve([
            'CONTRACT_ID' => 'CONTRACT-200',
            'MEMBER_PHONE_NUMBER' => '(555) 123-4567',
            'MEMBER_FIRST_NAME' => 'Jordan',
            'MEMBER_LAST_NAME' => 'Lee',
            'COVERAGE_EFFECTIVE_DATE' => '2026-05-15',
        ], $batch, $state);

        $this->assertSame(['health_sherpa'], $matchedSources);
        $this->assertFalse($wasOverridden);
        $this->assertSame('matched', $record['status']);
        $this->assertSame('HS:Phone+Date(±30d)', $record['match_method']);
        $this->assertSame('Taylor Sherpa', $record['aligned_agent_name']);
        $this->assertSame('HS-200', $record['ims_transaction_id']);
    }

    public function test_lock_list_override_wins_over_existing_matches(): void
    {
        $resolver = app(ReconciliationRecordResolver::class);
        $state = new ReconciliationLookupState;
        $batch = new ImportBatch;
        $batch->id = 'batch-300';

        $state->imsByEmail['override@example.com'] = [
            'transaction_id' => 'IMS-300',
            'agent_id' => 'AG-300',
            'agent_name' => 'Original Agent',
            'department' => 'Original Team',
        ];
        $state->payeeMap['original team'] = 'Original Payee';
        $state->lockList['CONTRACT-300'] = [
            'agent_name' => 'Locked Agent',
            'department' => 'Lock Team',
            'payee_name' => 'Lock Payee',
        ];

        [$record, $matchedSources, $wasOverridden] = $resolver->resolve([
            'CONTRACT_ID' => 'CONTRACT-300',
            'MEMBER_EMAIL_ADDRESS' => 'override@example.com',
            'MEMBER_FIRST_NAME' => 'Alex',
            'MEMBER_LAST_NAME' => 'Mason',
        ], $batch, $state);

        $this->assertSame(['ims'], $matchedSources);
        $this->assertTrue($wasOverridden);
        $this->assertSame('resolved', $record['status']);
        $this->assertSame('LockList Override', $record['match_method']);
        $this->assertTrue($record['override_flag']);
        $this->assertSame('lock_list', $record['override_source']);
        $this->assertSame('Locked Agent', $record['aligned_agent_name']);
        $this->assertSame('Lock Team', $record['group_team_sales']);
        $this->assertSame('Lock Payee', $record['payee_name']);
        $this->assertSame('Original Agent', $record['original_agent_name']);
        $this->assertSame('IMS:Email', $record['original_match_method']);
    }

    public function test_missing_contract_id_throws_a_clear_exception(): void
    {
        $resolver = app(ReconciliationRecordResolver::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing Contract ID / Policy ID in BOB row.');

        $resolver->resolve([
            'MEMBER_EMAIL_ADDRESS' => 'missing@example.com',
        ], tap(new ImportBatch, function (ImportBatch $batch) {
            $batch->id = 'batch-400';
        }), new ReconciliationLookupState);
    }
}
