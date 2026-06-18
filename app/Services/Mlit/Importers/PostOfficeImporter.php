<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P30: 郵便局
 * 属性: P30_001=行政区域コード, P30_002=公共施設大分類, P30_003=公共施設小分類,
 *       P30_004=郵便局分類, P30_005=名称, P30_006=所在地
 */
class PostOfficeImporter extends AbstractMlitImporter
{
    public function datasetCode(): string { return 'P30'; }

    public function datasetName(): string { return '郵便局'; }

    public function category(): string { return 'public'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        // GeoJSON: P30_005 / GML: name
        $name      = trim((string) ($properties['P30_005'] ?? $properties['name'] ?? ''));
        // GeoJSON: P30_001 / GML: administrativeArea
        $adminCode = trim((string) ($properties['P30_001'] ?? $properties['administrativeArea'] ?? ''));
        // GeoJSON: P30_006 / GML: address
        $address   = trim((string) ($properties['P30_006'] ?? $properties['address'] ?? ''));

        if ($name === '') {
            return null;
        }

        return [
            'sub_category'    => 'post_office',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'address'         => $address ?: null,
            'attributes'      => [
                'large_category' => $properties['P30_002'] ?? $properties['publicFacilityLargeClassification'] ?? null,
                'small_category' => $properties['P30_003'] ?? $properties['publicFacilitySmallClassification'] ?? null,
                'post_type'      => $properties['P30_004'] ?? $properties['type'] ?? null,
            ],
        ];
    }
}
