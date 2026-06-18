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
        // GeoJSON: P20_002 / GML: name
        $name      = trim((string) ($properties['P20_002'] ?? $properties['name'] ?? ''));
        // GeoJSON: P20_001 / GML: administrativeAreaCode
        $adminCode = trim((string) ($properties['P20_001'] ?? $properties['administrativeAreaCode'] ?? ''));
        // GeoJSON: P20_003 / GML: address
        $address   = trim((string) ($properties['P20_003'] ?? $properties['address'] ?? ''));

        if ($name === '') {
            return null;
        }

        return [
            'sub_category'    => 'evacuation_facility',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'address'         => $address ?: null,
            'attributes'      => [
                // GeoJSON: P20_004 / GML: facilityType
                'facility_type' => $properties['P20_004'] ?? $properties['facilityType'] ?? null,
                // GeoJSON: P20_005 / GML: seatingCapacity
                'capacity'      => isset($properties['P20_005']) ? (int) $properties['P20_005']
                    : (isset($properties['seatingCapacity']) ? (int) $properties['seatingCapacity'] : null),
                // GeoJSON: P20_006 / GML: facilityScale
                'area_m2'       => isset($properties['P20_006']) ? (float) $properties['P20_006']
                    : (isset($properties['facilityScale']) ? (float) $properties['facilityScale'] : null),
                // GML では hazardClassification 内の葉ノードがフラット化される
                'earthquake'    => $this->toBool($properties['P20_007'] ?? $properties['earthquakeHazard'] ?? null),
                'tsunami'       => $this->toBool($properties['P20_008'] ?? $properties['tsunamiHazard'] ?? null),
                'flood'         => $this->toBool($properties['P20_009'] ?? $properties['windAndFloodDamage'] ?? null),
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
