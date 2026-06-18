<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P04: 医療機関
 * 属性: P04_001=行政区域コード, P04_002=病院種別, P04_003=名称, P04_004=所在地
 */
class MedicalImporter extends AbstractMlitImporter
{
    public function datasetCode(): string { return 'P04'; }

    public function datasetName(): string { return '医療機関'; }

    public function category(): string { return 'medical'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        $name = trim((string) ($properties['P04_003'] ?? ''));

        if ($name === '') {
            return null;
        }

        $adminCode = trim((string) ($properties['P04_001'] ?? ''));

        return [
            'sub_category'    => 'medical_facility',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'address'         => trim((string) ($properties['P04_004'] ?? '')) ?: null,
            'attributes'      => [
                'facility_type' => $properties['P04_002'] ?? null,
            ],
        ];
    }
}
