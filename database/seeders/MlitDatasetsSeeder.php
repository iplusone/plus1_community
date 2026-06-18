<?php

namespace Database\Seeders;

use App\Models\MlitDataset;
use Illuminate\Database\Seeder;

class MlitDatasetsSeeder extends Seeder
{
    public function run(): void
    {
        $datasets = [
            [
                'code'         => 'P11',
                'name'         => 'バス停留所',
                'category'     => 'transport',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P11-v3_1.html',
                'source_year'  => '2010',
            ],
            [
                'code'         => 'P04',
                'name'         => '医療機関',
                'category'     => 'medical',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P04-v3_1.html',
                'source_year'  => null,
            ],
            [
                'code'         => 'P14',
                'name'         => '福祉施設',
                'category'     => 'welfare',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P14-v2_1.html',
                'source_year'  => '2021',
            ],
            [
                'code'         => 'A27',
                'name'         => '学校',
                'category'     => 'education',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-A27-v2_1.html',
                'source_year'  => '2021',
            ],
            [
                'code'         => 'P20',
                'name'         => '避難施設',
                'category'     => 'disaster',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P20-v2_4.html',
                'source_year'  => null,
            ],
            [
                'code'         => 'P35',
                'name'         => '道の駅',
                'category'     => 'industry',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P35-v1_1.html',
                'source_year'  => null,
            ],
            [
                'code'         => 'P17',
                'name'         => '消防署',
                'category'     => 'public',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/',
                'source_year'  => null,
            ],
            [
                'code'         => 'P18',
                'name'         => '警察署',
                'category'     => 'public',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/',
                'source_year'  => null,
            ],
            [
                'code'         => 'P30',
                'name'         => '郵便局',
                'category'     => 'public',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/',
                'source_year'  => null,
            ],
            [
                'code'         => 'P12',
                'name'         => '観光資源',
                'category'     => 'tourism',
                'license'      => 'CC BY 4.0',
                'download_url' => 'https://nlftp.mlit.go.jp/ksj/',
                'source_year'  => null,
            ],
        ];

        foreach ($datasets as $data) {
            MlitDataset::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
