<?php

namespace Tests\Unit\Reconciliation\ETL;

use App\Services\Reconciliation\ETL\ReconciliationValueNormalizer;
use Tests\TestCase;

class ReconciliationValueNormalizerTest extends TestCase
{
    public function test_flag_values_are_normalized_case_and_separator_insensitive(): void
    {
        $normalizer = app(ReconciliationValueNormalizer::class);

        $this->assertSame('Home Open', $normalizer->flagValue('HOME_OPEN'));
        $this->assertSame('Home Close', $normalizer->flagValue('home-close'));
        $this->assertNull($normalizer->flagValue('unknown'));
    }

    public function test_patch_id_normalization_handles_scientific_notation_and_decimal_suffixes(): void
    {
        $normalizer = app(ReconciliationValueNormalizer::class);

        $this->assertSame('123', $normalizer->patchId('123.0'));
        $this->assertSame('987', $normalizer->patchId('987.00'));
        $this->assertSame((string) (float) '1.23E+07', $normalizer->patchId('1.23E+07'));
    }

    public function test_date_normalization_supports_spaced_client_formats(): void
    {
        $normalizer = app(ReconciliationValueNormalizer::class);

        $this->assertSame('2026-04-08', $normalizer->date('08 04 26'));
        $this->assertSame('2026-04-08', $normalizer->date('08 04 2026'));
    }
}
