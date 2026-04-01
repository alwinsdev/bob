<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\ImportRowError;
use App\Models\ReconciliationQueue;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReconciliationETLService
{
    private FuzzyMatchService $fuzzyMatcher;

    public function __construct(FuzzyMatchService $fuzzyMatcher)
    {
        $this->fuzzyMatcher = $fuzzyMatcher;
    }

    public function processFile(string $filePath, ImportBatch $batch)
    {
        $batch->update(['status' => 'processing']);

        try {
            $reader = SimpleExcelReader::create($filePath)->headerOnRow(0); // auto-trim headers
            
            $total = 0;
            $processed = 0;
            $failed = 0;
            $chunkCounter = 0;
            $chunkSize = 250;
            $recordsChunk = [];

            $reader->getRows()->each(function(array $row) use (&$total, &$processed, &$failed, &$chunkCounter, &$recordsChunk, $batch, $chunkSize) {
                $total++;
                
                try {
                    $record = $this->transformAndMatch($row, $batch);
                    $recordsChunk[] = $record;
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $this->logRowError($batch, $total, $row, $e->getMessage());
                }

                $chunkCounter++;
                if ($chunkCounter >= $chunkSize) {
                    $this->flushChunk($recordsChunk);
                    $recordsChunk = [];
                    $chunkCounter = 0;
                }
            });

            // flush remaining
            if (!empty($recordsChunk)) {
                $this->flushChunk($recordsChunk);
            }

            $status = $failed > 0 ? 'completed_with_errors' : 'completed';
            $batch->update([
                'total_records' => $total,
                'processed_records' => $processed,
                'failed_records' => $failed,
                'status' => $status
            ]);
            
        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            Log::error("ETL Process failed for batch {$batch->id}: " . $e->getMessage());
        }
    }

    private function flushChunk(array $records)
    {
        ReconciliationQueue::insert($records);
    }

    private function transformAndMatch(array $row, ImportBatch $batch): array
    {
        // 1. Basic validation
        if (empty($row['Contract ID']) || empty($row['Effective Date'])) {
            throw new \Exception("Missing core required fields: Contract ID or Effective Date");
        }

        // 2. Encryption for PII
        $dob = !empty($row['Member DOB']) ? encrypt($row['Member DOB']) : null;
        $phone = !empty($row['Member Phone']) ? encrypt($row['Member Phone']) : null;

        // 3. IMS Data extraction
        $imsRecord = [
            'client_first_name' => $row['IMS Client First Name'] ?? null,
            'client_last_name' => $row['IMS Client Last Name'] ?? null,
            'client_email' => $row['IMS Client Email'] ?? null,
            'client_phone' => $row['IMS Client Phone'] ?? null,
        ];

        // 4. Run Match
        $matchResult = $this->fuzzyMatcher->match($imsRecord);
        
        $status = 'pending';
        if ($matchResult['confidence'] >= 90) {
            $status = 'matched';
        }

        return [
            'id' => (string) Str::ulid(),
            'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
            'import_batch_id' => $batch->id,
            'carrier' => $row['Carrier'] ?? null,
            'contract_id' => $row['Contract ID'],
            'product' => $row['Product'] ?? null,
            'member_first_name' => $row['Member First Name'] ?? null,
            'member_last_name' => $row['Member Last Name'] ?? null,
            'member_dob' => $dob,
            'member_email' => $row['Member Email'] ?? null,
            'member_phone' => $phone,
            'effective_date' => date('Y-m-d', strtotime($row['Effective Date'])),
            'ims_transaction_id' => $row['IMS Transaction ID'] ?? null,
            'client_first_name' => $imsRecord['client_first_name'],
            'client_last_name' => $imsRecord['client_last_name'],
            'client_email' => $imsRecord['client_email'],
            'client_phone' => $imsRecord['client_phone'],
            'match_confidence' => $matchResult['confidence'],
            'match_method' => $matchResult['method'],
            'field_scores' => json_encode($matchResult['scores']),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function logRowError(ImportBatch $batch, int $rowNum, array $rawData, string $msg)
    {
        ImportRowError::create([
            'import_batch_id' => $batch->id,
            'row_number' => $rowNum,
            'raw_data' => $rawData,
            'error_type' => 'validation',
            'error_messages' => ['error' => $msg],
        ]);
    }
}
