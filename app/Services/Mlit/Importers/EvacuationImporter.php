<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P20: 避難施設
 * 属性: P20_001=行政区域コード, P20_002=名称, P20_003=住所, P20_004=施設の種類,
 *       P20_005=収容人数, P20_006=施設規模(m²),
 *       P20_007=地震, P20_008=津波, P20_009=水害, P20_010=火山, P20_011=その他
 */
class EvacuationImporter extends AbstractMlitImporter
{
    public function datasetCode(): string { return 'P20'; }

    public function datasetName(): string { return '避難施設'; }

    public function category(): string { return 'disaster'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        $name = trim((string) ($properties['P20_002'] ?? ''));

        if ($name === '') {
            return null;
        }

        $adminCode = trim((string) ($properties['P20_001'] ?? ''));

        return [
            'sub_category'    => 'evacuation_facility',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'address'         => trim((string) ($properties['P20_003'] ?? '')) ?: null,
            'attributes'      => [
                'facility_type' => $properties['P20_004'] ?? null,
                'capacity'      => isset($properties['P20_005']) ? (int) $properties['P20_005'] : null,
                'area_m2'       => isset($properties['P20_006']) ? (float) $properties['P20_006'] : null,
                'earthquake'    => $this->toBool($properties['P20_007'] ?? null),
                'tsunami'       => $this->toBool($properties['P20_008'] ?? null),
                'flood'         => $this->toBool($properties['P20_009'] ?? null),
                'volcano'       => $this->toBool($properties['P20_010'] ?? null),
                'other'         => $this->toBool($properties['P20_011'] ?? null),
            ],
        ];
    }

    private function toBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return in_array($value, [true, 1, '1', 'true', 'TRUE'], true);
    }
}
