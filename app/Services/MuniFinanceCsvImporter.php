<?php

namespace App\Services;

use App\Models\Municipality;
use App\Models\MuniFinanceStat;
use Illuminate\Support\Facades\File;
use RuntimeException;

class MuniFinanceCsvImporter
{
    /**
     * @return array{rows:int,municipalities:int,skipped:int}
     */
    public function import(string $path, ?int $defaultYear = null, bool $defaultThreeYearAvg = false, bool $dryRun = false): array
    {
        if (! File::exists($path)) {
            throw new RuntimeException("CSVが見つかりません: {$path}");
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("CSVを開けませんでした: {$path}");
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            throw new RuntimeException('CSVヘッダを読めませんでした。');
        }

        $headerMap = array_flip(array_map('trim', $header));

        foreach (['jis_code', 'pref_code', 'pref_name', 'city_name'] as $required) {
            if (! array_key_exists($required, $headerMap)) {
                fclose($handle);
                throw new RuntimeException("必須列 {$required} がCSVにありません。");
            }
        }

        $result = [
            'rows' => 0,
            'municipalities' => 0,
            'skipped' => 0,
        ];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $record = [];

                foreach ($headerMap as $column => $index) {
                    $record[$column] = trim((string) ($row[$index] ?? ''));
                }

                $jisCode = substr(preg_replace('/\D+/', '', $record['jis_code']) ?? '', 0, 5);
                if (strlen($jisCode) !== 5) {
                    $result['skipped']++;
                    continue;
                }

                $year = $defaultYear ?? (isset($record['year']) && $record['year'] !== '' ? (int) $record['year'] : (int) date('Y'));
                $isThreeYearAvg = isset($record['is_three_year_avg']) && $record['is_three_year_avg'] !== ''
                    ? (bool) (int) $record['is_three_year_avg']
                    : $defaultThreeYearAvg;

                if (! $dryRun) {
                    Municipality::query()->updateOrCreate(
                        ['jis_code' => $jisCode],
                        [
                            'pref_code' => str_pad($record['pref_code'], 2, '0', STR_PAD_LEFT),
                            'pref_name' => $record['pref_name'],
                            'city_name' => $record['city_name'],
                            'city_kana' => $record['city_kana'] !== '' ? $record['city_kana'] : null,
                        ]
                    );

                    MuniFinanceStat::query()->updateOrCreate(
                        [
                            'year' => $year,
                            'jis_code' => $jisCode,
                            'is_three_year_avg' => $isThreeYearAvg,
                        ],
                        [
                            'fiscal_power_index' => $this->toDecimal($record['fiscal_power_index'] ?? ''),
                            'basic_fiscal_need' => $this->toInteger($record['basic_fiscal_need'] ?? ''),
                            'standard_tax_revenue' => $this->toInteger($record['standard_tax_revenue'] ?? ''),
                            'local_allocation_tax' => $this->toInteger($record['local_allocation_tax'] ?? ''),
                            'is_kofu' => $this->toNullableBool($record['is_kofu'] ?? ''),
                            'source_meta' => [
                                'file' => basename($path),
                                'header' => array_keys($headerMap),
                            ],
                        ]
                    );
                }

                $result['municipalities']++;
                $result['rows']++;
            }
        } finally {
            fclose($handle);
        }

        return $result;
    }

    private function toDecimal(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace([',', '％', '%'], ['.', '', ''], $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function toInteger(string $value): ?int
    {
        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value === '' ? null : (int) $value;
    }

    private function toNullableBool(string $value): ?bool
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return (bool) (int) $value;
    }
}
