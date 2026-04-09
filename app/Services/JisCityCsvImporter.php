<?php

namespace App\Services;

use App\Models\City;
use App\Models\Municipality;
use App\Models\Prefecture;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class JisCityCsvImporter
{
    /**
     * @return array{cities:int,municipalities:int,skipped:int}
     */
    public function import(string $path, bool $dryRun = false): array
    {
        if (! File::exists($path)) {
            throw new RuntimeException("CSVが見つかりません: {$path}");
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("CSVを開けませんでした: {$path}");
        }

        $result = [
            'cities' => 0,
            'municipalities' => 0,
            'skipped' => 0,
        ];

        $lineNumber = 0;

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if ($lineNumber === 1) {
                    continue;
                }

                $row = array_map(fn ($value): string => $this->normalizeCsvValue($value), $row);

                $code = trim((string) ($row[0] ?? ''));
                $prefName = trim((string) ($row[1] ?? ''));
                $cityName = trim((string) ($row[2] ?? ''));
                $cityKana = trim((string) ($row[4] ?? ''));

                if ($code === '' || $prefName === '' || $cityName === '') {
                    $result['skipped']++;
                    continue;
                }

                $prefecture = Prefecture::query()->where('name', $prefName)->first();
                if (! $prefecture) {
                    $result['skipped']++;
                    continue;
                }

                $prefCode = str_pad((string) $prefecture->id, 2, '0', STR_PAD_LEFT);
                $jisCode = substr(preg_replace('/\D+/', '', $code) ?? '', 0, 5);

                if (strlen($jisCode) !== 5) {
                    $result['skipped']++;
                    continue;
                }

                if (! $dryRun) {
                    Municipality::query()->updateOrCreate(
                        ['jis_code' => $jisCode],
                        [
                            'pref_code' => $prefCode,
                            'pref_name' => $prefName,
                            'city_name' => $cityName,
                            'city_kana' => $cityKana !== '' ? $cityKana : null,
                        ]
                    );

                    City::query()->updateOrCreate(
                        ['code' => $code],
                        [
                            'pref_id' => $prefecture->id,
                            'name' => $cityName,
                            'kana' => $cityKana !== '' ? $cityKana : null,
                        ]
                    );
                }

                $result['municipalities']++;
                $result['cities']++;
            }
        } finally {
            fclose($handle);
        }

        return $result;
    }

    private function normalizeCsvValue(mixed $value): string
    {
        $string = trim((string) $value);

        if ($string === '') {
            return '';
        }

        if (! mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'SJIS-win');
        }

        return Str::of($string)
            ->replace("\r", '')
            ->replace("\n", '')
            ->toString();
    }
}
