<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DownloadMlitCommand extends Command
{
    protected $signature = 'mlit:download
        {dataset? : データセットコード（P11 等）または省略で全件}
        {--pref=* : 都道府県コード（01-47）を指定（省略時は全47都道府県）}
        {--force : 既存ファイルを上書き}';

    protected $description = '国土数値情報（MLIT）GeoJSON/GML データを VPS の storage/app/mlit/ にダウンロードします';

    private const BASE = 'https://nlftp.mlit.go.jp/ksj/gml/data/';

    /**
     * データセット定義
     *
     * type=national : 全国一括ファイル
     * type=pref     : 都道府県別（url に {PREF} プレースホルダー）
     */
    // 正しいパターン: {BASE}/{CODE}/{CODE}-{YEAR}/{CODE}-{YEAR}[_{PREF}]_GML.zip
    private const DATASETS = [
        'P35' => ['type' => 'national', 'url' => self::BASE . 'P35/P35-18/P35-18_GML.zip'],
        'P30' => ['type' => 'national', 'url' => self::BASE . 'P30/P30-13/P30-13.zip'],
        'P11' => ['type' => 'national', 'url' => self::BASE . 'P11/P11-22/P11-22_GML.zip'],
        'P12' => ['type' => 'national', 'url' => self::BASE . 'P12/P12-14/P12-14_GML.zip'],
        'P20' => ['type' => 'pref', 'url' => self::BASE . 'P20/P20-12/P20-12_{PREF}_GML.zip'],
        'P04' => ['type' => 'pref', 'url' => self::BASE . 'P04/P04-20/P04-20_{PREF}_GML.zip'],
        'P14' => ['type' => 'pref', 'url' => self::BASE . 'P14/P14-15/P14-15_{PREF}_GML.zip'],
        'A27' => ['type' => 'pref', 'url' => self::BASE . 'A27/A27-16/A27-16_{PREF}_GML.zip'],
        'P17' => ['type' => 'pref', 'url' => self::BASE . 'P17/P17-12/P17-12_{PREF}_GML.zip'],
        'P18' => ['type' => 'pref', 'url' => self::BASE . 'P18/P18-12/P18-12_{PREF}_GML.zip'],
    ];

    private const PREFS = [
        '01','02','03','04','05','06','07','08','09','10',
        '11','12','13','14','15','16','17','18','19','20',
        '21','22','23','24','25','26','27','28','29','30',
        '31','32','33','34','35','36','37','38','39','40',
        '41','42','43','44','45','46','47',
    ];

    public function handle(): int
    {
        $code  = $this->argument('dataset') ? strtoupper($this->argument('dataset')) : null;
        $prefs = $this->option('pref') ?: self::PREFS;
        $force = (bool) $this->option('force');

        $targets = $code ? [$code => self::DATASETS[$code] ?? null] : self::DATASETS;

        foreach ($targets as $datasetCode => $def) {
            if ($def === null) {
                $this->error("[{$datasetCode}] 未定義のデータセット");
                continue;
            }

            if ($def['type'] === 'national') {
                $this->downloadFile($datasetCode, $def['url'], $force);
            } else {
                foreach ($prefs as $pref) {
                    $url = str_replace('{PREF}', $pref, $def['url']);
                    $this->downloadFile($datasetCode, $url, $force, $pref);
                }
            }
        }

        return self::SUCCESS;
    }

    private function downloadFile(string $code, string $url, bool $force, string $pref = ''): void
    {
        $dir  = storage_path("app/mlit/{$code}");
        $file = $dir . '/' . basename($url);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($file) && ! $force) {
            $this->line("[{$code}] スキップ（既存）: " . basename($file));
            return;
        }

        $label = $pref ? "[{$code}/{$pref}]" : "[{$code}]";
        $this->info("{$label} ダウンロード中: " . basename($url));

        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'follow_location' => 1,
                'timeout'         => 120,
                'header'          => "User-Agent: Mozilla/5.0 (compatible; plus1_community/1.0)\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);

        if ($data === false) {
            $this->warn("{$label} 失敗（404 または接続エラー）: {$url}");
            return;
        }

        file_put_contents($file, $data);
        $kb = round(strlen($data) / 1024, 1);
        $this->info("{$label} 完了 {$kb} KB → " . basename($file));
    }
}
