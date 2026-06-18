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

class ImportAllMlitCommand extends Command
{
    protected $signature = 'mlit:import-all
        {--dry-run : DB に書き込まず件数確認のみ}
        {--download : インポート前に mlit:download を実行する}
        {--force-download : ダウンロード時に既存ファイルを上書き}';

    protected $description = '全 MLIT データセットを storage/app/mlit/ から一括インポートします';

    /** 実行順序（データ量の小さい順） */
    private const ORDER = ['P35', 'P17', 'P18', 'P12', 'P30', 'A27', 'P20', 'P14', 'P04', 'P11'];

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
        if ($this->option('download')) {
            $forceFlag = $this->option('force-download') ? ['--force' => true] : [];
            $this->call('mlit:download', $forceFlag);
        }

        $dryRun  = (bool) $this->option('dry-run');
        $totals  = ['imported' => 0, 'skipped' => 0, 'errors' => 0];

        foreach (self::ORDER as $code) {
            $importerClass = $this->importers[$code] ?? null;

            if ($importerClass === null) {
                continue;
            }

            $dir   = storage_path("app/mlit/{$code}");
            $files = $this->resolveFiles($dir);

            if (empty($files)) {
                $this->warn("[{$code}] ファイルなし — スキップ（先に mlit:download を実行してください）");
                continue;
            }

            $this->info("[{$code}] インポート開始 (" . count($files) . " ファイル)");

            /** @var AbstractMlitImporter $importer */
            $importer = new $importerClass;
            $codeTotal = ['imported' => 0, 'skipped' => 0];

            foreach ($files as $file) {
                try {
                    $result = $importer->import($file, $dryRun);
                    $codeTotal['imported'] += $result['imported'];
                    $codeTotal['skipped']  += $result['skipped'];
                } catch (Throwable $e) {
                    $this->error("[{$code}] " . basename($file) . " 失敗: {$e->getMessage()}");
                    $totals['errors']++;
                }
            }

            $tag = $dryRun ? '（dry-run）' : '';
            $this->info("[{$code}] 完了{$tag} — imported: {$codeTotal['imported']}, skipped: {$codeTotal['skipped']}");

            $totals['imported'] += $codeTotal['imported'];
            $totals['skipped']  += $codeTotal['skipped'];
        }

        $this->newLine();
        $this->info('===== 全データセット完了 =====');
        $this->table(
            ['合計 imported', '合計 skipped', 'エラー数'],
            [[$totals['imported'], $totals['skipped'], $totals['errors']]]
        );

        return $totals['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** ディレクトリ内の GeoJSON / ZIP / XML を更新日時の新しい順で返す */
    private function resolveFiles(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = glob("{$dir}/*.{geojson,zip,xml}", GLOB_BRACE);

        if (empty($files)) {
            return [];
        }

        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        return $files;
    }
}
