<?php

namespace App\Exports\Concerns;

/**
 * Spreadsheet cell sanitizer.
 *
 * Excel/Google Sheets/LibreOffice all evaluate cell content starting with
 * `=`, `+`, `-`, `@`, TAB, or CR as a formula. A malicious upstream value
 * like `=cmd|'/c calc'!A1` becomes RCE the moment an analyst opens the
 * downloaded workbook. Prefixing a single quote (`'`) renders the value
 * as a literal string and Excel strips the quote on display.
 */
trait SanitizesSpreadsheetCells
{
    /** Characters that trigger formula evaluation in spreadsheet apps. */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    protected function sanitizeCell(mixed $value): mixed
    {
        if ($value === null || $value === '' || !is_string($value)) {
            return $value;
        }

        if (in_array($value[0], self::FORMULA_TRIGGERS, true)) {
            return "'" . $value;
        }

        return $value;
    }

    /** Sanitize every string element in a row. */
    protected function sanitizeRow(array $row): array
    {
        foreach ($row as $i => $cell) {
            $row[$i] = $this->sanitizeCell($cell);
        }
        return $row;
    }
}
