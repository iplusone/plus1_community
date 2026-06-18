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

        $file = $this->resolveFilePath($code);

        if ($file === null) {
            $this->error("インポートするファイルが見つかりません。--file または --dir を指定してください。");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info("[{$code}] dry-run モード: DB への書き込みは行いません");
        }

        $this->info("[{$code}] インポート開始: {$file}");

        try {
            /** @var AbstractMlitImporter $importer */
            $importer = new $this->importers[$code];
            $result = $importer->import($file, $dryRun);
        } catch (Throwable $e) {
            $this->error("インポート失敗: {$e->getMessage()}");

            return self::FAILURE;
        }

        $tag = $dryRun ? '（dry-run）' : '';
        $this->info("[{$code}] 完了{$tag} — imported: {$result['imported']}, skipped: {$result['skipped']}");

        return self::SUCCESS;
    }

    private function resolveFilePath(string $code): ?string
    {
        // --file 優先
        if ($file = $this->option('file')) {
            return $file;
        }

        // --dir/{code}/ 以下から GeoJSON / ZIP を探す
        $baseDir = $this->option('dir') ?? storage_path('app/mlit');
        $dir = rtrim($baseDir, '/') . '/' . $code;

        if (! is_dir($dir)) {
            return null;
        }

        $files = glob("{$dir}/*.{geojson,zip}", GLOB_BRACE);

        if (empty($files)) {
            return null;
        }

        // 最新ファイル（更新日時が新しいもの）を使用
        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        return $files[0];
    }
}
