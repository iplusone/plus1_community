<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Spot;
use App\Models\SpotWordpressSite;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class CitySpotCsvImporter
{
    private const REQUIRED_HEADERS = [
        'source_id',
        'jis_code',
        'prefecture',
        'city_name',
        'city_kana',
        'office_type',
        'spot_name',
        'postal_code',
        'address_line',
        'phone',
        'homepage_url',
        'latitude',
        'longitude',
        'description',
        'is_public',
        'published_at',
    ];

    /**
     * @return array{created:int,updated:int,wordpress_sites:int,skipped:int,company_id:int}
     */
    public function import(string $path, string $companyName = '全国自治体', bool $dryRun = false): array
    {
        if (! File::exists($path)) {
            throw new RuntimeException("CSVが見つかりません: {$path}");
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("CSVを開けませんでした: {$path}");
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);
            throw new RuntimeException('CSVヘッダを読めませんでした。');
        }

        $normalizedHeaders = array_map(
            static fn ($header): string => trim((string) $header),
            $headers
        );

        $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, $normalizedHeaders));
        if ($missingHeaders !== []) {
            fclose($handle);
            throw new RuntimeException('CSVヘッダが不足しています: '.implode(', ', $missingHeaders));
        }

        $company = Company::query()->firstOrCreate(
            ['name' => $companyName],
            ['status' => 'active', 'approved_at' => now()]
        );

        $result = [
            'created' => 0,
            'updated' => 0,
            'wordpress_sites' => 0,
            'skipped' => 0,
            'company_id' => $company->id,
        ];

        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null] || $values === false) {
                continue;
            }

            $row = $this->combineRow($normalizedHeaders, $values);

            if ($this->shouldSkip($row)) {
                $result['skipped']++;
                continue;
            }

            $slug = $this->resolveSlug($row);
            $spotData = $this->buildSpotPayload($company->id, $slug, $row);

            DB::transaction(function () use ($dryRun, &$result, $slug, $spotData, $row): void {
                $spot = Spot::query()->where('slug', $slug)->first();
                $exists = $spot !== null;

                if (! $dryRun) {
                    $spot ??= new Spot();
                    $spot->fill($spotData);
                    $spot->save();

                    $homepageUrl = trim((string) ($row['homepage_url'] ?? ''));
                    if ($homepageUrl !== '') {
                        SpotWordpressSite::query()->updateOrCreate(
                            ['spot_id' => $spot->id],
                            [
                                'base_url' => $homepageUrl,
                                'api_base_url' => null,
                                'username' => null,
                                'application_password' => null,
                                'is_active' => true,
                            ]
                        );

                        $result['wordpress_sites']++;
                    }
                }

                if ($exists) {
                    $result['updated']++;
                } else {
                    $result['created']++;
                }
            });
        }

        fclose($handle);

        return $result;
    }

    /**
     * @param list<string> $headers
     * @param list<string|null> $values
     * @return array<string, string>
     */
    private function combineRow(array $headers, array $values): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = trim((string) ($values[$index] ?? ''));
        }

        return $row;
    }

    /**
     * @param array<string, string> $row
     */
    private function shouldSkip(array $row): bool
    {
        foreach (['source_id', 'prefecture', 'city_name', 'spot_name', 'homepage_url', 'latitude', 'longitude'] as $field) {
            if (($row[$field] ?? '') === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $row
     */
    private function resolveSlug(array $row): string
    {
        $jisCode = preg_replace('/\D+/', '', (string) ($row['jis_code'] ?? '')) ?? '';
        $sourceId = preg_replace('/\D+/', '', (string) ($row['source_id'] ?? '')) ?? '';
        $primaryId = $jisCode !== '' ? $jisCode : $sourceId;

        if ($primaryId === '') {
            throw new RuntimeException('source_id または jis_code が不足しています。');
        }

        return 'municipal-office-'.$primaryId;
    }

    /**
     * @param array<string, string> $row
     * @return array<string, mixed>
     */
    private function buildSpotPayload(int $companyId, string $slug, array $row): array
    {
        $prefecture = trim((string) ($row['prefecture'] ?? ''));
        $cityName = trim((string) ($row['city_name'] ?? ''));
        $addressLine = trim((string) ($row['address_line'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));

        if ($description === '') {
            $description = $this->defaultDescription($prefecture, $cityName);
        }

        $fullAddress = trim($prefecture.$cityName.$addressLine);

        return [
            'company_id' => $companyId,
            'parent_id' => null,
            'depth' => 1,
            'name' => trim((string) ($row['spot_name'] ?? '')),
            'slug' => $slug,
            'postal_code' => $this->nullableValue($row['postal_code'] ?? ''),
            'prefecture' => $this->nullableValue($prefecture),
            'city' => $this->nullableValue($cityName),
            'town' => null,
            'address_line' => $this->nullableValue($addressLine),
            'phone' => $this->nullableValue($row['phone'] ?? ''),
            'description' => $description,
            'features' => null,
            'access_text' => $fullAddress !== '' ? $fullAddress : null,
            'business_hours_text' => null,
            'holiday_text' => null,
            'thumbnail_path' => null,
            'latitude' => $this->nullableFloat($row['latitude'] ?? ''),
            'longitude' => $this->nullableFloat($row['longitude'] ?? ''),
            'is_public' => $this->toBoolean($row['is_public'] ?? '1'),
            'published_at' => $this->nullableDatetime($row['published_at'] ?? ''),
            'view_count' => 0,
            'sort_order' => 0,
        ];
    }

    private function defaultDescription(string $prefecture, string $cityName): string
    {
        $location = trim($prefecture.' '.$cityName);

        return $location !== ''
            ? $location.'の自治体窓口情報です。'
            : '自治体窓口情報です。';
    }

    private function nullableValue(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableFloat(string $value): ?float
    {
        $value = trim($value);

        return $value === '' ? null : (float) $value;
    }

    private function toBoolean(string $value): bool
    {
        return in_array(Str::lower(trim($value)), ['1', 'true', 'yes'], true);
    }

    private function nullableDatetime(string $value): ?CarbonImmutable
    {
        $value = trim($value);

        return $value === '' ? null : CarbonImmutable::parse($value);
    }
}
