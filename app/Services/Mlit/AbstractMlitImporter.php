<?php

namespace App\Services\Mlit;

use App\Models\MlitDataset;
use App\Models\MlitSpot;
use RuntimeException;
use ZipArchive;

abstract class AbstractMlitImporter
{
    abstract public function datasetCode(): string;

    abstract public function datasetName(): string;

    abstract public function category(): string;

    /**
     * GeoJSON feature を mlit_spots の行データに変換する。
     * null を返すとそのフィーチャをスキップする。
     *
     * @param  array<string, mixed>  $properties
     * @param  array<string, mixed>  $geometry
     * @return array<string, mixed>|null
     */
    abstract protected function mapFeature(array $properties, array $geometry): ?array;

    /**
     * @return array{imported: int, skipped: int}
     */
    public function import(string $filePath, bool $dryRun = false): array
    {
        $geojsonPath = $this->resolveGeojsonPath($filePath);
        $content = file_get_contents($geojsonPath);

        if ($content === false) {
            throw new RuntimeException("ファイルを読み込めません: {$geojsonPath}");
        }

        $data = json_decode($content, true);

        if (! isset($data['features']) || ! is_array($data['features'])) {
            throw new RuntimeException("GeoJSON のフォーマットが不正です: {$geojsonPath}");
        }

        $now = now()->toDateTimeString();
        $imported = 0;
        $skipped = 0;
        $batch = [];

        foreach ($data['features'] as $feature) {
            $geometry = $feature['geometry'] ?? [];
            $properties = $feature['properties'] ?? [];

            // 点データ以外（線・面）は対象外
            if (($geometry['type'] ?? '') !== 'Point') {
                $skipped++;

                continue;
            }

            $coords = $geometry['coordinates'] ?? null;

            // GeoJSON は [経度, 緯度] の順
            if (! is_array($coords) || count($coords) < 2) {
                $skipped++;

                continue;
            }

            $mapped = $this->mapFeature($properties, $geometry);

            if ($mapped === null) {
                $skipped++;

                continue;
            }

            $lat = (float) $coords[1];
            $lng = (float) $coords[0];

            $row = array_merge([
                'dataset_code' => $this->datasetCode(),
                'category'     => $this->category(),
                'sub_category' => null,
                'pref_code'    => null,
                'admin_area_code' => null,
                'address'      => null,
                'attributes'   => null,
                'source_year'  => null,
            ], $mapped, [
                'latitude'     => $lat,
                'longitude'    => $lng,
                'imported_at'  => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // source_id が未設定なら座標 + 名前のハッシュで生成
            if (empty($row['source_id'])) {
                $row['source_id'] = md5(
                    round($lat, 5) . ',' . round($lng, 5) . ',' . ($row['name'] ?? '')
                );
            }

            // attributes は JSON 文字列として格納（upsert は Eloquent cast をバイパスするため）
            if (is_array($row['attributes'])) {
                $row['attributes'] = json_encode($row['attributes'], JSON_UNESCAPED_UNICODE);
            }

            $batch[] = $row;
            $imported++;

            if (count($batch) >= 500 && ! $dryRun) {
                $this->upsertBatch($batch);
                $batch = [];
            }
        }

        if (! $dryRun) {
            if (! empty($batch)) {
                $this->upsertBatch($batch);
            }

            MlitDataset::updateOrCreate(
                ['code' => $this->datasetCode()],
                [
                    'name'             => $this->datasetName(),
                    'category'         => $this->category(),
                    'last_imported_at' => $now,
                    'record_count'     => MlitSpot::where('dataset_code', $this->datasetCode())->count(),
                ]
            );
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function upsertBatch(array $rows): void
    {
        MlitSpot::upsert(
            $rows,
            ['dataset_code', 'source_id'],
            ['name', 'pref_code', 'admin_area_code', 'address', 'latitude', 'longitude', 'sub_category', 'attributes', 'source_year', 'imported_at', 'updated_at']
        );
    }

    private function resolveGeojsonPath(string $filePath): string
    {
        if (str_ends_with(strtolower($filePath), '.zip')) {
            return $this->extractGeojsonFromZip($filePath);
        }

        if (! file_exists($filePath)) {
            throw new RuntimeException("ファイルが見つかりません: {$filePath}");
        }

        return $filePath;
    }

    private function extractGeojsonFromZip(string $zipPath): string
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("ZIP ファイルを開けません: {$zipPath}");
        }

        $tmpDir = sys_get_temp_dir() . '/mlit_' . uniqid();
        mkdir($tmpDir, 0755, true);

        $geojsonFile = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (str_ends_with(strtolower($name), '.geojson')) {
                $zip->extractTo($tmpDir, $name);
                $geojsonFile = $tmpDir . '/' . $name;
                break;
            }
        }

        $zip->close();

        if ($geojsonFile === null) {
            throw new RuntimeException("ZIP に GeoJSON ファイルが見つかりません: {$zipPath}");
        }

        return $geojsonFile;
    }

    /** 行政区域コード（5桁）から都道府県コード（2桁）を抽出する */
    protected function prefCodeFromAdminCode(string $adminCode): ?string
    {
        $code = ltrim($adminCode, '0');

        return strlen($code) >= 2 ? substr(str_pad($adminCode, 5, '0', STR_PAD_LEFT), 0, 2) : null;
    }
}
