<?php

use App\Services\CitySpotCsvImporter;
use App\Services\JisCityCsvImporter;
use App\Services\MuniFinanceCsvImporter;
use App\Services\SourceRailwayBackupImporter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('spots:import-city-csv
    {path : 取り込むCSVファイルのパス}
    {--company=全国自治体 : company.name に使う値}
    {--dry-run : DBへ保存せず件数だけ確認する}', function (CitySpotCsvImporter $importer): int {
    $path = (string) $this->argument('path');
    $resolvedPath = Str::startsWith($path, ['/','./','../'])
        ? $path
        : base_path($path);

    $result = $importer->import(
        $resolvedPath,
        (string) $this->option('company'),
        (bool) $this->option('dry-run')
    );

    $this->info('CSVの取り込みが完了しました。');
    $this->line('company_id: '.$result['company_id']);
    $this->line('created: '.$result['created']);
    $this->line('updated: '.$result['updated']);
    $this->line('wordpress_sites: '.$result['wordpress_sites']);
    $this->line('skipped: '.$result['skipped']);

    if ((bool) $this->option('dry-run')) {
        $this->warn('dry-run のためDBへの保存は行っていません。');
    }

    return Command::SUCCESS;
})->purpose('自治体スポットCSVを spots と spot_wordpress_sites へ取り込む');

Artisan::command('railway:import-source-backups
    {source_dir : 元プロジェクトの backup SQL ディレクトリ}
    {--pref-code=12 : 取り込む都道府県コード}
    {--radius=5 : 近隣駅を再計算する半径(km)}
    {--dry-run : DBへ保存せず件数だけ確認する}', function (SourceRailwayBackupImporter $importer): int {
    $sourceDir = (string) $this->argument('source_dir');
    $resolvedDir = Str::startsWith($sourceDir, ['/','./','../'])
        ? $sourceDir
        : base_path($sourceDir);

    $result = $importer->import(
        $resolvedDir,
        (string) $this->option('pref-code'),
        (float) $this->option('radius'),
        (bool) $this->option('dry-run')
    );

    $this->info('鉄道バックアップの取り込みが完了しました。');
    $this->line('station_file: '.$result['station_file']);
    $this->line('route_file: '.$result['route_file']);
    $this->line('pivot_file: '.$result['pivot_file']);
    $this->line('stations: '.$result['stations']);
    $this->line('routes: '.$result['routes']);
    $this->line('route_stations: '.$result['route_stations']);
    $this->line('nearby_links: '.$result['nearby_links']);

    if ((bool) $this->option('dry-run')) {
        $this->warn('dry-run のためDBへの保存は行っていません。');
    }

    return Command::SUCCESS;
})->purpose('元プロジェクトの鉄道バックアップSQLから都道府県単位で駅・路線を取り込む');

Artisan::command('cities:import-jis-csv
    {path : 取り込むJIS市区町村CSVパス}
    {--dry-run : DBへ保存せず件数だけ確認する}', function (JisCityCsvImporter $importer): int {
    $path = (string) $this->argument('path');
    $resolvedPath = Str::startsWith($path, ['/','./','../'])
        ? $path
        : base_path($path);

    $result = $importer->import(
        $resolvedPath,
        (bool) $this->option('dry-run')
    );

    $this->info('JIS市区町村CSVの取り込みが完了しました。');
    $this->line('cities: '.$result['cities']);
    $this->line('municipalities: '.$result['municipalities']);
    $this->line('skipped: '.$result['skipped']);

    if ((bool) $this->option('dry-run')) {
        $this->warn('dry-run のためDBへの保存は行っていません。');
    }

    return Command::SUCCESS;
})->purpose('JIS市区町村CSVを cities / municipalities へ取り込む');

Artisan::command('mic:import-fiscal
    {path : 取り込む自治体財政CSVパス}
    {--year= : CSVに年度列がない場合の補完年度}
    {--three-year-avg=0 : 3か年平均データなら1}
    {--dry-run : DBへ保存せず件数だけ確認する}', function (MuniFinanceCsvImporter $importer): int {
    $path = (string) $this->argument('path');
    $resolvedPath = Str::startsWith($path, ['/','./','../'])
        ? $path
        : base_path($path);

    $result = $importer->import(
        $resolvedPath,
        $this->option('year') !== null ? (int) $this->option('year') : null,
        (bool) (int) $this->option('three-year-avg'),
        (bool) $this->option('dry-run')
    );

    $this->info('自治体財政CSVの取り込みが完了しました。');
    $this->line('rows: '.$result['rows']);
    $this->line('municipalities: '.$result['municipalities']);
    $this->line('skipped: '.$result['skipped']);

    if ((bool) $this->option('dry-run')) {
        $this->warn('dry-run のためDBへの保存は行っていません。');
    }

    return Command::SUCCESS;
})->purpose('自治体財政CSVを municipalities / muni_finance_stats へ取り込む');
