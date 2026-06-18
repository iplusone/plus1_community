<?php

namespace App\Services\Mlit;

use App\Models\MlitDataset;
use App\Models\MlitSpot;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

abstract class AbstractMlitImporter
{
    abstract public function datasetCode(): string;

    abstract public function datasetName(): string;

    abstract public function category(): string;

    /**
     * フィーチャーのプロパティと座標を mlit_spots 行データに変換する。
     * null を返すとそのフィーチャをスキップする。
     *
     * GeoJSON プロパティキー（P11_001 等）と
     * MLIT GML 要素名（ksj:P11_001 の localName）は同一仕様のため
     * GeoJSON / GML いずれの入力でも同じロジックで動作する。
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
        ['path' => $path, 'format' => $format] = $this->resolveDataFilePath($filePath);

        $features = $format === 'gml'
            ? $this->parseGml($path)
            : $this->parseGeoJson($path);

        return $this->importFeatures($features, $dryRun);
    }

    // -------------------------------------------------------------------------
    // パーサー
    // -------------------------------------------------------------------------

    /** @return array<int, array{geometry: array, properties: array}> */
    private function parseGeoJson(string $path): array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("ファイルを読み込めません: {$path}");
        }

        $data = json_decode($content, true);

        if (! isset($data['features']) || ! is_array($data['features'])) {
            throw new RuntimeException("GeoJSON のフォーマットが不正です: {$path}");
        }

        return array_map(fn ($f) => [
            'geometry'   => $f['geometry'] ?? [],
            'properties' => $f['properties'] ?? [],
        ], $data['features']);
    }

    /**
     * MLIT JPGIS GML (XML) を GeoJSON 互換フィーチャー配列に変換する。
     *
     * GML 座標は「緯度 経度」順（GeoJSON は「経度 緯度」の逆）。
     * 要素名は GeoJSON プロパティキーと同一（ksj:P11_001 → P11_001）。
     *
     * GML 3.1.1（2018年以前データ）と GML 3.2（2019年以降）の両方に対応するため
     * namespace URI はドキュメントから動的に検出する。
     *
     * @return array<int, array{geometry: array, properties: array}>
     */
    private function parseGml(string $path): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);

        if ($xml === false) {
            $errors = array_map(fn ($e) => $e->message, libxml_get_errors());
            throw new RuntimeException('GML の解析に失敗しました: ' . implode(', ', $errors));
        }

        // ドキュメントから namespace URI を動的に取得
        $namespaces = $xml->getNamespaces(true);
        $gmlNs = $ksjNs = null;

        foreach ($namespaces as $uri) {
            if ($gmlNs === null && str_contains($uri, 'opengis.net/gml')) {
                $gmlNs = $uri;
            }
            if ($ksjNs === null && str_contains($uri, 'nlftp.mlit.go.jp')) {
                $ksjNs = $uri;
            }
        }

        if ($gmlNs === null || $ksjNs === null) {
            throw new RuntimeException("GML の namespace を検出できません: {$path}");
        }

        $features = [];

        foreach ($xml->children($gmlNs)->featureMember as $member) {
            foreach ($member->children($ksjNs) as $feature) {
                $geometry   = null;
                $properties = [];

                foreach ($feature->children($ksjNs) as $localName => $child) {
                    if ($localName === 'position') {
                        // <ksj:position><gml:Point><gml:pos>LAT LON</gml:pos></gml:Point></ksj:position>
                        $point = $child->children($gmlNs)->Point ?? null;
                        if ($point) {
                            $pos   = trim((string) $point->children($gmlNs)->pos);
                            $parts = preg_split('/\s+/', $pos);
                            if (count($parts) >= 2) {
                                // GML は LAT LON 順 → GeoJSON [LON, LAT] に変換
                                $geometry = [
                                    'type'        => 'Point',
                                    'coordinates' => [(float) $parts[1], (float) $parts[0]],
                                ];
                            }
                        }
                    } else {
                        $properties[$localName] = (string) $child;
                    }
                }

                if ($geometry !== null) {
                    $features[] = ['geometry' => $geometry, 'properties' => $properties];
                }
            }
        }

        return $features;
    }

    // -------------------------------------------------------------------------
    // DB 書き込み
    // -------------------------------------------------------------------------

    /**
     * @param  array<int, array{geometry: array, properties: array}>  $features
     * @return array{imported: int, skipped: int}
     */
    private function importFeatures(array $features, bool $dryRun): array
    {
        $now      = now()->toDateTimeString();
        $imported = 0;
        $skipped  = 0;
        $batch    = [];

        foreach ($features as $feature) {
            $geometry   = $feature['geometry'];
            $properties = $feature['properties'];

            if (($geometry['type'] ?? '') !== 'Point') {
                $skipped++;
                continue;
            }

            $coords = $geometry['coordinates'] ?? null;

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
                'dataset_code'    => $this->datasetCode(),
                'category'        => $this->category(),
                'sub_category'    => null,
                'pref_code'       => null,
                'admin_area_code' => null,
                'address'         => null,
                'attributes'      => null,
                'source_year'     => null,
            ], $mapped, [
                'latitude'    => $lat,
                'longitude'   => $lng,
                'imported_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            if (empty($row['source_id'])) {
                $row['source_id'] = md5(
                    round($lat, 5) . ',' . round($lng, 5) . ',' . ($row['name'] ?? '')
                );
            }

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

    // -------------------------------------------------------------------------
    // ファイル解決
    // -------------------------------------------------------------------------

    /** @return array{path: string, format: 'geojson'|'gml'} */
    private function resolveDataFilePath(string $filePath): array
    {
        $lower = strtolower($filePath);

        if (str_ends_with($lower, '.zip')) {
            return $this->extractFromZip($filePath);
        }

        if (! file_exists($filePath)) {
            throw new RuntimeException("ファイルが見つかりません: {$filePath}");
        }

        $format = str_ends_with($lower, '.geojson') ? 'geojson' : 'gml';

        return ['path' => $filePath, 'format' => $format];
    }

    /** @return array{path: string, format: 'geojson'|'gml'} */
    private function extractFromZip(string $zipPath): array
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("ZIP ファイルを開けません: {$zipPath}");
        }

        $tmpDir = sys_get_temp_dir() . '/mlit_' . uniqid();
        mkdir($tmpDir, 0755, true);

        $found  = null;
        $format = 'gml';

        // GeoJSON を優先、なければ XML（GML）を使う
        for ($pass = 0; $pass < 2; $pass++) {
            $ext = $pass === 0 ? '.geojson' : '.xml';
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_ends_with(strtolower($name), $ext)) {
                    $zip->extractTo($tmpDir, $name);
                    $found  = $tmpDir . '/' . $name;
                    $format = $pass === 0 ? 'geojson' : 'gml';
                    break 2;
                }
            }
        }

        $zip->close();

        if ($found === null) {
            throw new RuntimeException("ZIP に GeoJSON / XML ファイルが見つかりません: {$zipPath}");
        }

        return ['path' => $found, 'format' => $format];
    }

    /** 行政区域コード（5桁）から都道府県コード（2桁）を抽出する */
    protected function prefCodeFromAdminCode(string $adminCode): ?string
    {
        $padded = str_pad($adminCode, 5, '0', STR_PAD_LEFT);

        return substr($padded, 0, 2);
    }
}
