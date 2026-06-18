<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * A27: 学校（公立小学校）
 * 属性: A27_001=行政区域コード, A27_002=設置主体, A27_003=名称, A27_004=所在地
 */
class SchoolImporter extends AbstractMlitImporter
{
    public function datasetCode(): string { return 'A27'; }

    public function datasetName(): string { return '学校'; }

    public function category(): string { return 'education'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        // GeoJSON: A27_003 / GML: name
        $name      = trim((string) ($properties['A27_003'] ?? $properties['name'] ?? ''));
        // GeoJSON: A27_001 / GML: administrativeAreaCode
        $adminCode = trim((string) ($properties['A27_001'] ?? $properties['administrativeAreaCode'] ?? ''));
        // GeoJSON: A27_004 / GML: address
        $address   = trim((string) ($properties['A27_004'] ?? $properties['address'] ?? ''));
        // GeoJSON: A27_002 / GML: establishmentBody
        $estBody   = $properties['A27_002'] ?? $properties['establishmentBody'] ?? null;

        if ($name === '') {
            return null;
        }

        return [
            'sub_category'    => 'school',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'address'         => $address ?: null,
            'attributes'      => [
                'establishment_type' => $estBody,
            ],
        ];
    }
}
