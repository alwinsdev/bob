<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ImportBatch;
use App\Models\ReconciliationQueue;
use App\Models\ReconciliationAuditLog;
use App\Models\Agent;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_US');
        $manager = User::where('email', 'manager@bob.test')->first();
        $analyst = User::where('email', 'analyst@bob.test')->first();
        
        // 1. Create a dummy Import Batch
        $batch = ImportBatch::create([
            'carrier_file_path' => 'demo/Bob_Export_CarrierA.csv',
            'carrier_original_name' => 'Bob_Export_CarrierA.csv',
            'ims_file_path' => 'demo/IMS_Export_April.csv',
            'ims_original_name' => 'IMS_Export_April.csv',
            'total_records' => 500,
            'processed_records' => 500,
            'failed_records' => 0,
            'skipped_duplicates' => 0,
            'status' => 'completed',
            'uploaded_by' => $manager?->id,
        ]);

        $agents = Agent::where('is_active', true)->get();
        if ($agents->isEmpty()) {
            return;
        }

        // 2. Generate Records
        $carriers = ['Anthem', 'Cigna', 'Aetna', 'UHC'];
        $products = ['Medicare Advantage', 'Commercial HMO', 'Commercial PPO', 'Dental'];
        
        $recordsToInsert = [];
        $auditLogsToInsert = [];
        
        for ($i = 0; $i < 500; $i++) {
            $isMatched = $faker->boolean(30); // 30% exactly matched
            $isPending = !$isMatched && $faker->boolean(70); // Among remaining, 70% pending
            $isResolved = !$isMatched && !$isPending && $faker->boolean(50);
            $isFlagged = !$isMatched && !$isPending && !$isResolved;

            $status = 'pending';
            if ($isMatched) $status = 'matched';
            if ($isResolved) $status = 'resolved';
            if ($isFlagged) $status = 'flagged';

            $confidence = $isMatched ? $faker->randomFloat(2, 90, 100) : $faker->randomFloat(2, 40, 89);
            if ($status === 'resolved' || $status === 'flagged') {
                $confidence = $faker->randomFloat(2, 40, 95);
            }

            $memberFirst = $faker->firstName;
            $memberLast = $faker->lastName;
            $dob = $faker->date('1950-01-01', '2000-12-31');
            $phone = $faker->numerify('(###) ###-####');

            $agent = $agents->random();
            $hasAgent = $status === 'resolved';

            $recordsToInsert[] = [
                'id' => (string) Str::ulid(),
                'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
                'import_batch_id' => $batch->id,
                
                'carrier' => $faker->randomElement($carriers),
                'contract_id' => 'CNT-' . $faker->numberBetween(10000, 99999),
                'product' => $faker->randomElement($products),
                
                'member_first_name' => $memberFirst,
                'member_last_name' => $memberLast,
                'member_dob' => encrypt($dob),
                'member_email' => strtolower($memberFirst . '.' . $memberLast . '@example.com'),
                'member_phone' => encrypt($phone),
                'effective_date' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),

                'ims_transaction_id' => 'IMS-' . $faker->numberBetween(1000, 9999),
                'client_first_name' => $isMatched ? $memberFirst : $faker->firstName,
                'client_last_name' => $isMatched ? $memberLast : $faker->lastName,
                'client_email' => $isMatched ? strtolower($memberFirst . '.' . $memberLast . '@example.com') : $faker->email,
                'client_phone' => $isMatched ? $phone : $faker->numerify('(###) ###-####'),
                
                'match_confidence' => $confidence,
                'match_method' => $confidence >= 90 ? 'email' : 'name',
                'field_scores' => json_encode([
                    'first_name' => $isMatched ? 1.0 : 0.6,
                    'last_name' => $isMatched ? 1.0 : 0.8,
                    'email' => $isMatched ? 1.0 : 0.2,
                    'phone' => $isMatched ? 1.0 : 0.4,
                ]),

                'status' => $status,
                
                'aligned_agent_code' => $hasAgent ? $agent->agent_code : null,
                'aligned_agent_name' => $hasAgent ? $agent->full_name : null,
                'group_team_sales' => $hasAgent ? $agent->group_team_sales : null,
                'payee_name' => $hasAgent ? $agent->full_name : null,
                'compensation_type' => $hasAgent ? $faker->randomElement(['New', 'Renewal']) : null,

                'resolved_by' => $hasAgent ? $analyst?->id : null,
                'resolved_at' => $hasAgent ? now() : null,

                'created_at' => now(),
                'updated_at' => now(),
            ];

            $transactionId = $recordsToInsert[$i]['transaction_id'];
            if ($status === 'resolved' || $status === 'flagged') {
                $auditLogsToInsert[] = [
                    'id' => (string) Str::ulid(),
                    'transaction_id' => $transactionId,
                    'action' => $status,
                    'previous_values' => null,
                    'new_values' => null,
                    'previous_agent_code' => null,
                    'new_agent_code' => $status === 'resolved' ? $agent->agent_code : null,
                    'modified_by_user_id' => $analyst?->id,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'demo-seeder',
                    'created_at' => now(),
                ];
            }
        }

        foreach (array_chunk($recordsToInsert, 100) as $chunk) {
            ReconciliationQueue::insert($chunk);
        }

        foreach (array_chunk($auditLogsToInsert, 200) as $chunk) {
            ReconciliationAuditLog::insert($chunk);
        }
    }
}
