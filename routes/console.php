<?php

use App\Services\CitySpotCsvImporter;
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
