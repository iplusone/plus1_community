<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Spot;
use App\Models\SpotWordpressSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportCitySpotsCsvCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_city_spots_csv_into_spots(): void
    {
        $directory = storage_path('app/testing');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/city-spots.csv';

        file_put_contents($path, implode("\n", [
            'source_id,jis_code,prefecture,city_name,city_kana,office_type,spot_name,postal_code,address_line,phone,homepage_url,latitude,longitude,description,is_public,published_at',
            '13,13113,東京都,渋谷区,しぶやく,ward_office,渋谷区役所,,,,'.
                'https://www.city.shibuya.tokyo.jp,35.6629,139.7039,,1,2026-03-31 00:00:00',
        ]));

        Artisan::call('spots:import-city-csv', [
            'path' => $path,
            '--company' => '自治体データ',
        ]);

        $company = Company::query()->where('name', '自治体データ')->first();

        $this->assertNotNull($company);

        $spot = Spot::query()->where('slug', 'municipal-office-13113')->first();

        $this->assertNotNull($spot);
        $this->assertSame('渋谷区役所', $spot->name);
        $this->assertSame('東京都', $spot->prefecture);
        $this->assertSame('渋谷区', $spot->city);
        $this->assertTrue($spot->is_public);
        $this->assertSame($company->id, $spot->company_id);

        $wordpressSite = SpotWordpressSite::query()->where('spot_id', $spot->id)->first();

        $this->assertNotNull($wordpressSite);
        $this->assertSame('https://www.city.shibuya.tokyo.jp', $wordpressSite->base_url);
    }

    public function test_it_updates_existing_spot_on_reimport(): void
    {
        $directory = storage_path('app/testing');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/city-spots-update.csv';

        file_put_contents($path, implode("\n", [
            'source_id,jis_code,prefecture,city_name,city_kana,office_type,spot_name,postal_code,address_line,phone,homepage_url,latitude,longitude,description,is_public,published_at',
            '13,13113,東京都,渋谷区,しぶやく,ward_office,渋谷区役所,,,,'.
                'https://www.city.shibuya.tokyo.jp,35.6629,139.7039,,1,2026-03-31 00:00:00',
        ]));

        Artisan::call('spots:import-city-csv', ['path' => $path]);

        file_put_contents($path, implode("\n", [
            'source_id,jis_code,prefecture,city_name,city_kana,office_type,spot_name,postal_code,address_line,phone,homepage_url,latitude,longitude,description,is_public,published_at',
            '13,13113,東京都,渋谷区,しぶやく,ward_office,渋谷区役所,150-8010,宇田川町1-1,03-3463-1211,'.
                'https://www.city.shibuya.tokyo.jp,35.6630,139.7040,渋谷区の公式窓口です,1,2026-03-31 00:00:00',
        ]));

        Artisan::call('spots:import-city-csv', ['path' => $path]);

        $spot = Spot::query()->where('slug', 'municipal-office-13113')->firstOrFail();

        $this->assertSame('150-8010', $spot->postal_code);
        $this->assertSame('宇田川町1-1', $spot->address_line);
        $this->assertSame('03-3463-1211', $spot->phone);
        $this->assertSame('渋谷区の公式窓口です', $spot->description);
        $this->assertEquals(35.6630, $spot->latitude);
        $this->assertEquals(139.7040, $spot->longitude);
        $this->assertSame(1, Spot::query()->where('slug', 'municipal-office-13113')->count());
    }
}
