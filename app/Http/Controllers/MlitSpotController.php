<?php

namespace App\Http\Controllers;

use App\Models\MlitSpot;
use Illuminate\Contracts\View\View;

class MlitSpotController extends Controller
{
    private const SUB_CATEGORY_LABELS = [
        'medical_facility'    => '医療機関',
        'welfare_facility'    => '福祉施設',
        'school'              => '学校',
        'fire_station'        => '消防署',
        'police'              => '警察署',
        'post_office'         => '郵便局',
        'evacuation_facility' => '避難施設',
        'roadside_station'    => '道の駅',
        'tourism_resource'    => '観光資源',
    ];

    private const CATEGORY_LABELS = [
        'medical'   => '医療機関',
        'welfare'   => '福祉施設',
        'education' => '学校',
        'public'    => '公共施設',
        'disaster'  => '避難施設',
        'tourism'   => '観光・道の駅',
    ];

    private const ATTR_LABELS = [
        'adminCode'           => '行政区域コード',
        'type'                => '種別',
        'typeCode'            => '種別コード',
        'establishmentBody'   => '設置主体',
        'prefectureName'      => '都道府県',
        'cityName'            => '市区町村',
        'earthquakeHazard'    => '地震',
        'tsunamiHazard'       => '津波',
        'windAndFloodDamage'  => '風水害',
        'highTide'            => '高潮',
        'landslide'           => '土砂崩れ',
        'volcano'             => '火山',
        'fire'                => '火事',
        'capacity'            => '収容人数',
        'facilityName'        => '施設名',
        'classification'      => '分類',
        'operator'            => '運営者',
        'sourceYear'          => 'データ年度',
    ];

    public static function attrLabel(string $key): string
    {
        return self::ATTR_LABELS[$key] ?? $key;
    }

    public function show(int $id): View
    {
        $spot = MlitSpot::with('dataset')->findOrFail($id);

        $categoryLabel    = self::CATEGORY_LABELS[$spot->category] ?? $spot->category;
        $subCategoryLabel = self::SUB_CATEGORY_LABELS[$spot->sub_category] ?? $spot->sub_category;

        $mapQuery = $spot->address;
        if ($spot->latitude && $spot->longitude) {
            $mapQuery = "{$spot->latitude},{$spot->longitude}";
        }

        $attrs = collect($spot->attributes ?? [])
            ->map(fn ($v) => is_bool($v) ? ($v ? 'あり' : 'なし') : $v)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();

        return view('mlit-spots.show', compact(
            'spot',
            'categoryLabel',
            'subCategoryLabel',
            'mapQuery',
            'attrs',
        ));
    }
}
