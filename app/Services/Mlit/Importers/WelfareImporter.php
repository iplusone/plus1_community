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
        $name = trim((string) ($properties['P14_008'] ?? ''));

        if ($name === '') {
            return null;
        }

        $adminCode = trim((string) ($properties['P14_003'] ?? ''));

        return [
            'sub_category'    => 'welfare_facility',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'address'         => trim((string) ($properties['P14_004'] ?? '')) ?: null,
            'attributes'      => [
                'large_category'  => $properties['P14_005'] ?? null,
                'medium_category' => $properties['P14_006'] ?? null,
                'small_category'  => $properties['P14_007'] ?? null,
                'manager_code'    => $properties['P14_009'] ?? null,
            ],
        ];
    }
}
