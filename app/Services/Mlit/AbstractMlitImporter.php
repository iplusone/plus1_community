<?php

namespace App\Services\Mlit;

use App\Models\MlitDataset;
use App\Models\MlitSpot;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
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
        $imported = 0;
        $skipped  = 0;

        foreach ($this->resolveDataFiles($filePath) as ['path' => $path, 'format' => $format]) {
            try {
                $features = $format === 'gml'
                    ? $this->parseGml($path)
                    : $this->parseGeoJson($path);
            } catch (RuntimeException) {
                // 壊れた個別ファイルはスキップして続行
                continue;
            }

            $result    = $this->importFeatures($features, $dryRun);
            $imported += $result['imported'];
            $skipped  += $result['skipped'];
        }

        return ['imported' => $imported, 'skipped' => $skipped];
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
     * MLIT の GML 形式は以下の2パターンが存在する：
     *
     * パターンA（旧形式 / P17消防署・P18警察署等）:
     *   <gml:Point gml:id="pt_xxx"> が Dataset 直下に並び、
     *   フィーチャー要素 <ksj:pos xlink:href="#pt_xxx"/> で参照する。
     *   プロパティ名は短縮コード（fsn, aac, ccd, adr 等）。
     *
     * パターンB（新形式 / P35道の駅等 GML版）:
     *   <gml:featureMember> でフィーチャーを囲み、
     *   <ksj:position><gml:Point>…</gml:Point></ksj:position> で座標を内包する。
     *   プロパティ名は P35_001 等の長形式。
     *
     * @return array<int, array{geometry: array, properties: array}>
     */
    private function parseGml(string $path): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();

        // LIBXML_RECOVER: P12等の一部データにタグ属性の欠損があるため、可能な限り復元して継続
        // 1=LIBXML_RECOVER, 32=LIBXML_NOERROR, 64=LIBXML_NOWARNING
        $loaded = $dom->load($path, 1 | 32 | 64);
        libxml_clear_errors();

        if (! $loaded || $dom->documentElement === null) {
            throw new RuntimeException("GML の解析に失敗しました（復元不可）: {$path}");
        }

        $root   = $dom->documentElement;
        $gmlNs  = $root->lookupNamespaceURI('gml') ?? '';
        $ksjNs  = $root->lookupNamespaceURI('ksj') ?? '';

        // 全 xmlns 属性から再検出（プレフィックスが異なる場合の補完）
        if ($gmlNs === '' || $ksjNs === '') {
            foreach ($root->attributes as $attr) {
                $uri = $attr->value;
                if ($gmlNs === '' && str_contains($uri, 'opengis.net/gml')) {
                    $gmlNs = $uri;
                }
                if ($ksjNs === '' && str_contains($uri, 'nlftp.mlit.go.jp')) {
                    $ksjNs = $uri;
                }
            }
        }

        if ($gmlNs === '' || $ksjNs === '') {
            throw new RuntimeException("GML の namespace を検出できません: {$path}");
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('gml', $gmlNs);
        $xpath->registerNamespace('ksj', $ksjNs);
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        // ------------------------------------------------------------------
        // パターンA: Dataset 直下の <gml:Point> を ID → 座標マップに収集
        // ------------------------------------------------------------------
        $pointMap = [];
        foreach ($xpath->query('gml:Point', $root) as $point) {
            $id = $point->getAttributeNS($gmlNs, 'id');
            if ($id === '') {
                // GML 3.1.1 では gml:id ではなく gml:id が無名前空間属性の場合もある
                $id = $point->getAttribute('gml:id');
            }
            $posNodes = $xpath->query('gml:pos', $point);
            if ($id !== '' && $posNodes->length > 0) {
                $parts = preg_split('/\s+/', trim($posNodes->item(0)->textContent));
                if (count($parts) >= 2) {
                    // GML は LAT LON 順 → GeoJSON [LON, LAT]
                    $pointMap[$id] = [(float) $parts[1], (float) $parts[0]];
                }
            }
        }

        $features = [];

        // ------------------------------------------------------------------
        // パターンB: <gml:featureMember> ラッパーを使う新形式
        // ------------------------------------------------------------------
        $featureMembers = $xpath->query('gml:featureMember', $root);
        if ($featureMembers->length > 0) {
            foreach ($featureMembers as $member) {
                foreach ($member->childNodes as $node) {
                    if (! ($node instanceof DOMElement)) {
                        continue;
                    }
                    $f = $this->extractDomFeature($node, $xpath, $gmlNs, $pointMap);
                    if ($f !== null) {
                        $features[] = $f;
                    }
                }
            }

            return $features;
        }

        // ------------------------------------------------------------------
        // パターンA: ksj フィーチャーが Dataset 直下に並ぶ形式
        // ------------------------------------------------------------------
        foreach ($xpath->query('ksj:*', $root) as $node) {
            $f = $this->extractDomFeature($node, $xpath, $gmlNs, $pointMap);
            if ($f !== null) {
                $features[] = $f;
            }
        }

        return $features;
    }

    /**
     * DOMElement からフィーチャーの座標とプロパティを抽出する。
     *
     * @param  array<string, array<float>>  $pointMap  gml:id → [lon, lat]
     * @return array{geometry: array, properties: array}|null
     */
    private function extractDomFeature(DOMElement $node, DOMXPath $xpath, string $gmlNs, array $pointMap): ?array
    {
        $geometry   = null;
        $properties = [];

        foreach ($node->childNodes as $child) {
            if (! ($child instanceof DOMElement)) {
                continue;
            }

            $localName = $child->localName;

            // 座標参照要素: "pos"（旧 JPGIS）または "position"（新形式）
            if ($localName === 'pos' || $localName === 'position') {
                $href = $child->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
                if ($href !== '') {
                    // xlink:href="#pt_xxx" → pointMap を参照
                    $pointId = ltrim($href, '#');
                    if (isset($pointMap[$pointId])) {
                        $geometry = ['type' => 'Point', 'coordinates' => $pointMap[$pointId]];
                    }
                } else {
                    // インライン <gml:Point><gml:pos>…</gml:pos></gml:Point>
                    $posNodes = $xpath->query('.//gml:pos', $child);
                    if ($posNodes->length > 0) {
                        $parts = preg_split('/\s+/', trim($posNodes->item(0)->textContent));
                        if (count($parts) >= 2) {
                            $geometry = [
                                'type'        => 'Point',
                                'coordinates' => [(float) $parts[1], (float) $parts[0]],
                            ];
                        }
                    }
                }
            } else {
                $properties[$localName] = trim($child->textContent);
            }
        }

        // プロパティがあればgeometryがなくても返す（住所のみ登録用）
        return ! empty($properties) ? ['geometry' => $geometry, 'properties' => $properties] : null;
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
            $geometry   = $feature['geometry'] ?? null;
            $properties = $feature['properties'];

            // Point 以外（ポリゴン等）は座標なしで登録（住所のみのケース）
            $isPoint = ($geometry['type'] ?? '') === 'Point';
            $coords  = $isPoint ? ($geometry['coordinates'] ?? null) : null;

            if ($isPoint && (! is_array($coords) || count($coords) < 2)) {
                $skipped++;
                continue;
            }

            $mapped = $this->mapFeature($properties, $geometry ?? []);

            if ($mapped === null) {
                $skipped++;
                continue;
            }

            $lat = ($coords !== null) ? (float) $coords[1] : null;
            $lng = ($coords !== null) ? (float) $coords[0] : null;

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
                // 座標がある場合は座標ベース、ない場合は名称+住所ベースのID
                $row['source_id'] = $lat !== null
                    ? md5(round($lat, 5) . ',' . round($lng, 5) . ',' . ($row['name'] ?? ''))
                    : md5($this->datasetCode() . ':' . ($row['name'] ?? '') . ':' . ($row['address'] ?? ''));
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

    /**
     * ZIP・GeoJSON・XML を受け取り、処理対象ファイルのリストを返す。
     * ZIP 内に複数の XML が含まれる場合（P12 等）は全件返す。
     *
     * @return array<int, array{path: string, format: 'geojson'|'gml'}>
     */
    private function resolveDataFiles(string $filePath): array
    {
        $lower = strtolower($filePath);

        if (str_ends_with($lower, '.zip')) {
            return $this->extractFromZip($filePath);
        }

        if (! file_exists($filePath)) {
            throw new RuntimeException("ファイルが見つかりません: {$filePath}");
        }

        $format = str_ends_with($lower, '.geojson') ? 'geojson' : 'gml';

        return [['path' => $filePath, 'format' => $format]];
    }

    /**
     * ZIP を一時ディレクトリに展開し、処理対象ファイルの一覧を返す。
     * GeoJSON が含まれる場合は GeoJSON のみを返す（GML より優先）。
     * 複数の XML が含まれる場合（P12 等）は全件返す。
     * KS-META-*.xml はスキップする。
     *
     * @return array<int, array{path: string, format: 'geojson'|'gml'}>
     */
    private function extractFromZip(string $zipPath): array
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("ZIP ファイルを開けません: {$zipPath}");
        }

        $tmpDir = sys_get_temp_dir() . '/mlit_' . uniqid();
        mkdir($tmpDir, 0755, true);

        $geojsons = [];
        $xmls     = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name     = $zip->getNameIndex($i);
            $basename = basename($name);
            $lower    = strtolower($name);

            if (str_starts_with($basename, 'KS-META-')) {
                continue;
            }

            if (str_ends_with($lower, '.geojson')) {
                $zip->extractTo($tmpDir, $name);
                $geojsons[] = $tmpDir . '/' . $name;
            } elseif (str_ends_with($lower, '.xml')) {
                $zip->extractTo($tmpDir, $name);
                $xmls[] = $tmpDir . '/' . $name;
            }
        }

        $zip->close();

        // GeoJSON が含まれていれば XML は無視（P35 等）
        if (! empty($geojsons)) {
            sort($geojsons);

            return array_map(fn ($p) => ['path' => $p, 'format' => 'geojson'], $geojsons);
        }

        if (! empty($xmls)) {
            sort($xmls);

            return array_map(fn ($p) => ['path' => $p, 'format' => 'gml'], $xmls);
        }

        throw new RuntimeException("ZIP に GeoJSON / XML ファイルが見つかりません: {$zipPath}");
    }

    /** 行政区域コード（5桁）から都道府県コード（2桁）を抽出する */
    protected function prefCodeFromAdminCode(string $adminCode): ?string
    {
        $padded = str_pad($adminCode, 5, '0', STR_PAD_LEFT);

        return substr($padded, 0, 2);
    }
}
