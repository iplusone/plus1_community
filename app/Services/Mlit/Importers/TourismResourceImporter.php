<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P12: 観光資源
 * 属性: PrefCd=都道府県コード, AdminAreaCd=行政区域コード, ResourceName=資源名称, ResourceType=資源種別
 */
class TourismResourceImporter extends AbstractMlitImporter
{
    public function datasetCode(): string { return 'P12'; }

    public function datasetName(): string { return '観光資源'; }

    public function category(): string { return 'tourism'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        $name = trim((string) ($properties['ResourceName'] ?? ''));

        if ($name === '') {
            return null;
        }

        $prefCode = trim((string) ($properties['PrefCd'] ?? ''));
        $adminCode = trim((string) ($properties['AdminAreaCd'] ?? ''));

        return [
            'sub_category'    => 'tourism_resource',
            'name'            => $name,
            'pref_code'       => $prefCode ?: ($adminCode ? $this->prefCodeFromAdminCode($adminCode) : null),
            'admin_area_code' => $adminCode ?: null,
            'attributes'      => [
                'resource_type' => $properties['ResourceType'] ?? null,
            ],
        ];
    }
}
