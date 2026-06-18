<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P14: 福祉施設
 * 属性: P14_001=都道府県名, P14_002=市区町村名, P14_003=行政区域コード,
 *       P14_004=所在地, P14_005=大分類, P14_006=中分類, P14_007=小分類, P14_008=名称
 */
class WelfareImporter extends AbstractMlitImporter
{
    public function datasetCode(): string { return 'P14'; }

    public function datasetName(): string { return '福祉施設'; }

    public function category(): string { return 'welfare'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        // GeoJSON: P14_008 / GML: name
        $name      = trim((string) ($properties['P14_008'] ?? $properties['name'] ?? ''));
        // GeoJSON: P14_003 / GML: 行政区域コードなし（administrativeCode は施設管理コード）
        $adminCode = trim((string) ($properties['P14_003'] ?? ''));
        // GeoJSON: P14_004 / GML: address（都道府県・市区町村は別要素）
        $address   = trim((string) ($properties['P14_004'] ?? $properties['address'] ?? ''));

        if ($name === '') {
            return null;
        }

        // GML では都道府県名+市区町村名+住所を結合してフルアドレスを構成
        if ($address !== '' && isset($properties['prefectureName'])) {
            $pref = trim((string) $properties['prefectureName']);
            $city = trim((string) ($properties['cityName'] ?? ''));
            $address = $pref . $city . $address;
        }

        return [
            'sub_category'    => 'welfare_facility',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'address'         => $address ?: null,
            'attributes'      => [
                'large_category'  => $properties['P14_005'] ?? $properties['publicFacilityLargeClassification'] ?? null,
                'medium_category' => $properties['P14_006'] ?? null,
                'small_category'  => $properties['P14_007'] ?? $properties['publicFacilitySmallClassification'] ?? null,
                'capacity'        => isset($properties['capacity']) ? (int) $properties['capacity'] : null,
            ],
        ];
    }
}
