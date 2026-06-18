<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P12: 観光資源（面データ → 座標なし・住所のみで登録）
 *
 * GML 要素名（タイポはデータ側の仕様）:
 *   turismResorceName        = 資源名称
 *   prefectureCode           = 都道府県コード
 *   administartiveAreaCode   = 行政区域コード
 *   turismResorceKindName    = 資源種別名
 *   address                  = 住所
 *   tourismResourceCategoryCode = カテゴリーコード
 */
class TourismResourceImporter extends AbstractMlitImporter
{
    public function datasetCode(): string { return 'P12'; }

    public function datasetName(): string { return '観光資源'; }

    public function category(): string { return 'tourism'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        // GML 旧形式
        $name = trim((string) ($properties['turismResorceName'] ?? $properties['ResourceName'] ?? ''));

        if ($name === '') {
            return null;
        }

        $prefCode  = trim((string) ($properties['prefectureCode'] ?? $properties['PrefCd'] ?? ''));
        $adminCode = trim((string) ($properties['administartiveAreaCode'] ?? $properties['AdminAreaCd'] ?? ''));
        $kindName  = trim((string) ($properties['turismResorceKindName'] ?? $properties['ResourceType'] ?? ''));
        $address   = trim((string) ($properties['address'] ?? ''));

        return [
            'sub_category'    => 'tourism_resource',
            'name'            => $name,
            'pref_code'       => $prefCode ?: ($adminCode ? $this->prefCodeFromAdminCode($adminCode) : null),
            'admin_area_code' => $adminCode ?: null,
            'address'         => $address ?: null,
            'attributes'      => [
                'kind_name'     => $kindName ?: null,
                'category_code' => $properties['tourismResourceCategoryCode'] ?? null,
            ],
        ];
    }
}
