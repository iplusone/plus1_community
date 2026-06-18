<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P35: 道の駅
 * 属性: P35_001=緯度, P35_002=経度, P35_003=都道府県名, P35_004=市町村名,
 *       P35_005=行政区域コード, P35_006=道の駅名, P35_007~010=URL,
 *       P35_011~028=施設フラグ（1=あり, 2=なし）
 */
class RoadsideStationImporter extends AbstractMlitImporter
{
    private const FACILITY_KEYS = [
        'P35_011' => 'atm',
        'P35_012' => 'baby_bed',
        'P35_013' => 'restaurant',
        'P35_014' => 'snack_bar',
        'P35_015' => 'lodging',
        'P35_016' => 'hot_spring',
        'P35_017' => 'campsite',
        'P35_018' => 'park',
        'P35_019' => 'observation_deck',
        'P35_020' => 'museum',
        'P35_021' => 'gas_station',
        'P35_022' => 'ev_charger',
        'P35_023' => 'wifi',
        'P35_024' => 'shower',
        'P35_025' => 'experience',
        'P35_026' => 'tourist_info',
        'P35_027' => 'accessible_toilet',
        'P35_028' => 'shop',
    ];

    public function datasetCode(): string { return 'P35'; }

    public function datasetName(): string { return '道の駅'; }

    public function category(): string { return 'industry'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        $name = trim((string) ($properties['P35_006'] ?? ''));

        if ($name === '') {
            return null;
        }

        $adminCode = trim((string) ($properties['P35_005'] ?? ''));

        $urls = array_values(array_filter([
            $properties['P35_007'] ?? null,
            $properties['P35_008'] ?? null,
            $properties['P35_009'] ?? null,
            $properties['P35_010'] ?? null,
        ], fn ($v) => ! empty(trim((string) $v))));

        $facilities = [];

        foreach (self::FACILITY_KEYS as $key => $label) {
            if (isset($properties[$key])) {
                $facilities[$label] = ((int) $properties[$key]) === 1;
            }
        }

        return [
            'sub_category'    => 'roadside_station',
            'name'            => $name,
            'pref_code'       => $adminCode ? $this->prefCodeFromAdminCode($adminCode) : null,
            'admin_area_code' => $adminCode ?: null,
            'attributes'      => [
                'prefecture' => trim((string) ($properties['P35_003'] ?? '')) ?: null,
                'city'       => trim((string) ($properties['P35_004'] ?? '')) ?: null,
                'urls'       => $urls,
                'facilities' => $facilities,
            ],
        ];
    }
}
