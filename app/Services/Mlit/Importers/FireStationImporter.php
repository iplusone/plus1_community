<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P17: 消防署
 * 属性: P17_001=名称, P17_002=行政区域コード, P17_003=種別（1=消防本部,2=消防署,3=分署・出張所）, P17_004=所在地
 */
class FireStationImporter extends AbstractMlitImporter
{
    private const TYPE_LABELS = [
        1 => 'headquarters',
        2 => 'fire_station',
        3 => 'substation',
    ];

    public function datasetCode(): string { return 'P17'; }

    public function datasetName(): string { return '消防署'; }

    public function category(): string { return 'public'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        // GeoJSON: P17_001〜004 / GML（旧 JPGIS）: fsn, aac, ccd, adr
        $name      = trim((string) ($properties['P17_001'] ?? $properties['fsn'] ?? ''));
        $adminCode = trim((string) ($properties['P17_002'] ?? $properties['aac'] ?? ''));
        $typeProp  = $properties['P17_003'] ?? $properties['ccd'] ?? null;
        $address   = trim((string) ($properties['P17_004'] ?? $properties['adr'] ?? ''));
        $typeCode  = $typeProp !== null ? (int) $typeProp : null;

        if ($name === '') {
            return null;
        }

        return [
            'sub_category'    => 'fire_station',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'address'         => $address ?: null,
            'attributes'      => [
                'type_code'  => $typeCode,
                'type_label' => $typeCode ? (self::TYPE_LABELS[$typeCode] ?? null) : null,
            ],
        ];
    }
}
