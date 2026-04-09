<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrefecturesTableSeeder extends Seeder
{
    public function run(): void
    {
        $prefectures = [
            ['id' => 1, 'name' => '北海道', 'code' => '01'],
            ['id' => 2, 'name' => '青森県', 'code' => '02'],
            ['id' => 3, 'name' => '岩手県', 'code' => '03'],
            ['id' => 4, 'name' => '宮城県', 'code' => '04'],
            ['id' => 5, 'name' => '秋田県', 'code' => '05'],
            ['id' => 6, 'name' => '山形県', 'code' => '06'],
            ['id' => 7, 'name' => '福島県', 'code' => '07'],
            ['id' => 8, 'name' => '茨城県', 'code' => '08'],
            ['id' => 9, 'name' => '栃木県', 'code' => '09'],
            ['id' => 10, 'name' => '群馬県', 'code' => '10'],
            ['id' => 11, 'name' => '埼玉県', 'code' => '11'],
            ['id' => 12, 'name' => '千葉県', 'code' => '12'],
            ['id' => 13, 'name' => '東京都', 'code' => '13'],
            ['id' => 14, 'name' => '神奈川県', 'code' => '14'],
            ['id' => 15, 'name' => '新潟県', 'code' => '15'],
            ['id' => 16, 'name' => '富山県', 'code' => '16'],
            ['id' => 17, 'name' => '石川県', 'code' => '17'],
            ['id' => 18, 'name' => '福井県', 'code' => '18'],
            ['id' => 19, 'name' => '山梨県', 'code' => '19'],
            ['id' => 20, 'name' => '長野県', 'code' => '20'],
            ['id' => 21, 'name' => '岐阜県', 'code' => '21'],
            ['id' => 22, 'name' => '静岡県', 'code' => '22'],
            ['id' => 23, 'name' => '愛知県', 'code' => '23'],
            ['id' => 24, 'name' => '三重県', 'code' => '24'],
            ['id' => 25, 'name' => '滋賀県', 'code' => '25'],
            ['id' => 26, 'name' => '京都府', 'code' => '26'],
            ['id' => 27, 'name' => '大阪府', 'code' => '27'],
            ['id' => 28, 'name' => '兵庫県', 'code' => '28'],
            ['id' => 29, 'name' => '奈良県', 'code' => '29'],
            ['id' => 30, 'name' => '和歌山県', 'code' => '30'],
            ['id' => 31, 'name' => '鳥取県', 'code' => '31'],
            ['id' => 32, 'name' => '島根県', 'code' => '32'],
            ['id' => 33, 'name' => '岡山県', 'code' => '33'],
            ['id' => 34, 'name' => '広島県', 'code' => '34'],
            ['id' => 35, 'name' => '山口県', 'code' => '35'],
            ['id' => 36, 'name' => '徳島県', 'code' => '36'],
            ['id' => 37, 'name' => '香川県', 'code' => '37'],
            ['id' => 38, 'name' => '愛媛県', 'code' => '38'],
            ['id' => 39, 'name' => '高知県', 'code' => '39'],
            ['id' => 40, 'name' => '福岡県', 'code' => '40'],
            ['id' => 41, 'name' => '佐賀県', 'code' => '41'],
            ['id' => 42, 'name' => '長崎県', 'code' => '42'],
            ['id' => 43, 'name' => '熊本県', 'code' => '43'],
            ['id' => 44, 'name' => '大分県', 'code' => '44'],
            ['id' => 45, 'name' => '宮崎県', 'code' => '45'],
            ['id' => 46, 'name' => '鹿児島県', 'code' => '46'],
            ['id' => 47, 'name' => '沖縄県', 'code' => '47'],
        ];

        DB::table('prefectures')->upsert($prefectures, ['id'], ['name', 'code']);
    }
}
