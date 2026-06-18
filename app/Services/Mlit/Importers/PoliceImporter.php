<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P18: 警察署
 * 属性: P18_001=名称, P18_002=行政区域コード, P18_003=種別コード, P18_004=所在地
 */
class PoliceImporter extends AbstractMlitImporter
{
    public function datasetCode(): string { return 'P18'; }

    public function datasetName(): string { return '警察署'; }

    public function category(): string { return 'public'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        // GeoJSON: P18_001〜004 / GML（旧 JPGIS）: psn, aac, ccd, adr
        $name      = trim((string) ($properties['P18_001'] ?? $properties['psn'] ?? ''));
        $adminCode = trim((string) ($properties['P18_002'] ?? $properties['aac'] ?? ''));
        $typeCode  = $properties['P18_003'] ?? $properties['ccd'] ?? null;
        $address   = trim((string) ($properties['P18_004'] ?? $properties['adr'] ?? ''));

        if ($name === '') {
            return null;
        }

        return [
            'sub_category'    => 'police',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'address'         => $address ?: null,
            'attributes'      => [
                'type_code' => $typeCode !== null ? (int) $typeCode : null,
            ],
        ];
    }
}
