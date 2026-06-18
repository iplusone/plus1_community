<?php

namespace App\Console\Commands;

use App\Services\Mlit\AbstractMlitImporter;
use App\Services\Mlit\Importers\BusStopImporter;
use App\Services\Mlit\Importers\EvacuationImporter;
use App\Services\Mlit\Importers\FireStationImporter;
use App\Services\Mlit\Importers\MedicalImporter;
use App\Services\Mlit\Importers\PoliceImporter;
use App\Services\Mlit\Importers\PostOfficeImporter;
use App\Services\Mlit\Importers\RoadsideStationImporter;
use App\Services\Mlit\Importers\SchoolImporter;
use App\Services\Mlit\Importers\TourismResourceImporter;
use App\Services\Mlit\Importers\WelfareImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportMlitCommand extends Command
{
    protected $signature = 'import:mlit
        {dataset : データセットコード（P11, P04, P14, A27, P20, P35, P17, P18, P30, P12）}
        {--file= : GeoJSON または ZIP ファイルの絶対パス}
        {--dir= : データセットコードをサブディレクトリ名とした GeoJSON 格納ディレクトリ（--file 未指定時に検索）}
        {--dry-run : DB に書き込まず件数確認のみ実施}';

    protected $description = '国土数値情報（MLIT）スポットデータを mlit_spots テーブルにインポートします';

    /** @var array<string, class-string<AbstractMlitImporter>> */
    private array $importers = [
        'P11' => BusStopImporter::class,
        'P04' => MedicalImporter::class,
        'P14' => WelfareImporter::class,
        'A27' => SchoolImporter::class,
        'P20' => EvacuationImporter::class,
        'P35' => RoadsideStationImporter::class,
        'P17' => FireStationImporter::class,
        'P18' => PoliceImporter::class,
        'P30' => PostOfficeImporter::class,
        'P12' => TourismResourceImporter::class,
    ];

    public function handle(): int
    {
        $code = strtoupper($this->argument('dataset'));

        if (! isset($this->importers[$code])) {
            $this->error("不明なデータセットコード: {$code}");
            $this->line('利用可能: ' . implode(', ', array_keys($this->importers)));

            return self::FAILURE;
        }

        $files = $this->resolveFiles($code);

        if (empty($files)) {
            $this->error("インポートするファイルが見つかりません。--file または --dir を指定してください。");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info("[{$code}] dry-run モード: DB への書き込みは行いません");
        }

        /** @var AbstractMlitImporter $importer */
        $importer = new $this->importers[$code];
        $total = ['imported' => 0, 'skipped' => 0];

        $this->info("[{$code}] インポート開始 (" . count($files) . " ファイル)");

        foreach ($files as $file) {
            try {
                $result = $importer->import($file, $dryRun);
                $total['imported'] += $result['imported'];
                $total['skipped']  += $result['skipped'];
                $this->line("  " . basename($file) . " → imported: {$result['imported']}, skipped: {$result['skipped']}");
            } catch (Throwable $e) {
                $this->error("  " . basename($file) . " 失敗: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        $tag = $dryRun ? '（dry-run）' : '';
        $this->info("[{$code}] 完了{$tag} — imported: {$total['imported']}, skipped: {$total['skipped']}");

        return self::SUCCESS;
    }

    /** @return string[] */
    private function resolveFiles(string $code): array
    {
        // --file 優先（単一ファイル指定）
        if ($file = $this->option('file')) {
            return [$file];
        }

        $baseDir = $this->option('dir') ?? storage_path('app/mlit');
        $dir     = rtrim($baseDir, '/') . '/' . $code;

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob("{$dir}/*.{geojson,zip,xml}", GLOB_BRACE);

        if (empty($files)) {
            return [];
        }

        sort($files); // ファイル名順（都道府県コード順）

        return $files;
    }
}
