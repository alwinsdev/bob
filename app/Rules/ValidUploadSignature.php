<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class ValidUploadSignature implements ValidationRule
{
    public function __construct(
        private readonly ?string $label = null,
        private readonly int $maxZipEntries = 5000,
        private readonly int $maxExpandedBytes = 209715200
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $extension = strtolower((string) $value->getClientOriginalExtension());
        $path = $value->getRealPath();

        if (! $path || ! file_exists($path)) {
            return;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $fail(sprintf('The %s could not be opened for validation.', $this->subject($attribute)));
            return;
        }

        $header = fread($handle, 8);
        fclose($handle);

        if ($header === false || strlen($header) < 4) {
            $fail(sprintf('The %s appears to be empty or unreadable.', $this->subject($attribute)));
            return;
        }

        $bytes = array_values(unpack('C*', $header));

        switch ($extension) {
            case 'xlsx':
                if (! $this->matchesBytes($bytes, [0x50, 0x4B, 0x03, 0x04])) {
                    $fail(sprintf('The %s does not appear to be a valid Excel (.xlsx) file.', $this->subject($attribute)));
                    return;
                }

                $this->validateWorkbookArchive($path, $fail, $attribute);
                return;

            case 'xls':
                if (! $this->matchesBytes($bytes, [0xD0, 0xCF, 0x11, 0xE0])) {
                    $fail(sprintf('The %s does not appear to be a valid Excel (.xls) file.', $this->subject($attribute)));
                }
                return;

            case 'csv':
            case 'txt':
                $this->validateTextFile($bytes, $fail, $attribute);
                return;

            default:
                return;
        }
    }

    private function validateWorkbookArchive(string $path, Closure $fail, string $attribute): void
    {
        if (! class_exists(ZipArchive::class)) {
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            $fail(sprintf('The %s could not be opened as a valid Excel workbook.', $this->subject($attribute)));
            return;
        }

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > $this->maxZipEntries) {
                $fail(sprintf('The %s contains an unsafe number of archive entries.', $this->subject($attribute)));
                return;
            }

            if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName('xl/workbook.xml') === false) {
                $fail(sprintf('The %s is missing required Excel workbook metadata.', $this->subject($attribute)));
                return;
            }

            $expandedBytes = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if (! is_array($stat)) {
                    continue;
                }

                $expandedBytes += max(0, (int) ($stat['size'] ?? 0));

                if ($expandedBytes > $this->maxExpandedBytes) {
                    $fail(sprintf('The %s expands to an unsafe size when decompressed.', $this->subject($attribute)));
                    return;
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function validateTextFile(array $bytes, Closure $fail, string $attribute): void
    {
        foreach ([
            [0x50, 0x4B],
            [0xD0, 0xCF],
            [0x4D, 0x5A],
            [0x7F, 0x45, 0x4C, 0x46],
        ] as $signature) {
            if ($this->matchesBytes($bytes, $signature)) {
                $fail(sprintf('The %s does not appear to be a valid CSV or text file.', $this->subject($attribute)));
                return;
            }
        }

        $controlBytes = 0;
        foreach ($bytes as $byte) {
            if ($byte === 0x00) {
                $fail(sprintf('The %s contains binary data and is not a valid text file.', $this->subject($attribute)));
                return;
            }

            if ($byte < 0x09 || ($byte > 0x0D && $byte < 0x20)) {
                $controlBytes++;
            }
        }

        if ($controlBytes >= 2) {
            $fail(sprintf('The %s contains unexpected binary control bytes.', $this->subject($attribute)));
        }
    }

    /**
     * @param  array<int, int>  $actual
     * @param  array<int, int>  $expected
     */
    private function matchesBytes(array $actual, array $expected): bool
    {
        foreach ($expected as $index => $byte) {
            if (($actual[$index] ?? null) !== $byte) {
                return false;
            }
        }

        return true;
    }

    private function subject(string $attribute): string
    {
        if ($this->label !== null && trim($this->label) !== '') {
            return $this->label . ' file';
        }

        return str_replace('_', ' ', $attribute) . ' file';
    }
}
