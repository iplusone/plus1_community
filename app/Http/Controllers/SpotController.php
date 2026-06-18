<?php

namespace App\Http\Controllers;

use App\Models\MlitSpot;
use App\Models\RailwayRoute;
use App\Models\Spot;
use App\Models\Station;
use App\Models\StationNearStation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SpotController extends Controller
{
    /** mlit_spots.category → 表示名 */
    private const CATEGORY_LABELS = [
        'medical'   => '医療機関',
        'welfare'   => '福祉施設',
        'education' => '学校',
        'public'    => '公共施設',
        'disaster'  => '避難施設',
        'tourism'   => '観光・道の駅',
    ];

    /** mlit_spots.sub_category → 表示名 */
    private const SUB_CATEGORY_LABELS = [
        'medical_facility'      => '医療機関',
        'welfare_facility'      => '福祉施設',
        'school'                => '学校',
        'fire_station'          => '消防署',
        'police'                => '警察署',
        'post_office'           => '郵便局',
        'evacuation_facility'   => '避難施設',
        'roadside_station'      => '道の駅',
        'tourism_resource'      => '観光資源',
    ];

    public function index(Request $request): View
    {
        $q        = trim((string) $request->string('q'));
        $area     = trim((string) $request->string('area'));
        $category = trim((string) $request->string('category'));
        $sort     = $request->string('sort')->value() ?: 'name';
        $view     = $request->string('view')->value() ?: 'card';
        $perPage  = 50;
        $page     = $request->integer('page', 1);

        $dbWarning = null;
        $spots     = collect();
        $total     = 0;

        try {
            // ---------------------------------------------------------------
            // spots テーブル（公開済み）
            // ---------------------------------------------------------------
            $spotsQ = DB::table('spots')
                ->select([
                    DB::raw("'spot' as source"),
                    DB::raw("CAST(id AS CHAR) as source_id"),
                    'name',
                    'slug',
                    DB::raw("TRIM(CONCAT_WS(' ', COALESCE(prefecture,''), COALESCE(city,''), COALESCE(town,''), COALESCE(address_line,''))) as full_address"),
                    DB::raw("'' as category"),
                    DB::raw("'' as sub_category"),
                    DB::raw("NULL as latitude"),
                    DB::raw("NULL as longitude"),
                    DB::raw("NULL as pref_code"),
                    'published_at',
                    'view_count',
                ])
                ->where('is_public', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());

            if ($q !== '') {
                $spotsQ->where(fn ($b) => $b
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('prefecture', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('town', 'like', "%{$q}%")
                    ->orWhere('address_line', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                );
            }

            if ($area !== '') {
                $this->applyAreaFilter($spotsQ, $area);
            }

            // カテゴリ指定時は spots を除外（mlit_spots のみ）
            if ($category !== '') {
                $spotsQ->whereRaw('0=1');
            }

            // ---------------------------------------------------------------
            // mlit_spots テーブル
            // ---------------------------------------------------------------
            $mlitQ = DB::table('mlit_spots')
                ->select([
                    DB::raw("'mlit' as source"),
                    DB::raw("CAST(id AS CHAR) as source_id"),
                    'name',
                    DB::raw("NULL as slug"),
                    DB::raw("COALESCE(address, '') as full_address"),
                    'category',
                    'sub_category',
                    'latitude',
                    'longitude',
                    'pref_code',
                    DB::raw("NULL as published_at"),
                    DB::raw("0 as view_count"),
                ]);

            if ($q !== '') {
                $mlitQ->where(fn ($b) => $b
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                );
            }

            if ($area !== '') {
                $mlitQ->where(fn ($b) => $b
                    ->where('address', 'like', "%{$area}%")
                );
            }

            if ($category !== '') {
                $mlitQ->where(fn ($b) => $b
                    ->where('category', $category)
                    ->orWhere('sub_category', $category)
                );
            }

            // ---------------------------------------------------------------
            // UNION & ページネーション
            // ---------------------------------------------------------------
            $union = $spotsQ->unionAll($mlitQ);

            $countSql  = "SELECT COUNT(*) as cnt FROM ({$union->toSql()}) as u";
            $total     = DB::selectOne($countSql, $union->getBindings())->cnt;

            $orderCol = match ($sort) {
                'popular' => 'view_count DESC, name ASC',
                default   => 'name ASC',
            };

            $rows = DB::select(
                "SELECT * FROM ({$union->toSql()}) as u ORDER BY {$orderCol} LIMIT ? OFFSET ?",
                array_merge($union->getBindings(), [$perPage, ($page - 1) * $perPage])
            );

            $spots = new LengthAwarePaginator(
                collect($rows),
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } catch (\Throwable $e) {
            $dbWarning = 'データベースエラーが発生しました: ' . $e->getMessage();
        }

        return view('spots.index', [
            'spots'           => $spots,
            'total'           => $total,
            'filters'         => $request->only(['q', 'area', 'category', 'sort', 'view']),
            'sort'            => in_array($sort, ['name', 'popular'], true) ? $sort : 'name',
            'viewMode'        => in_array($view, ['card', 'list'], true) ? $view : 'card',
            'categoryLabels'  => self::CATEGORY_LABELS,
            'subCategoryLabels' => self::SUB_CATEGORY_LABELS,
            'dbWarning'       => $dbWarning,
        ]);
    }

    public function show(string $slug): View
    {
        try {
            $spot = Spot::query()
                ->visible()
                ->with([
                    'parent',
                    'children',
                    'genres',
                    'tags',
                    'businessHours',
                    'services.menus',
                    'media',
                    'staff',
                    'coupons',
                    'wordpressSite',
                    'spotStations.station.railwayRoutes',
                ])
                ->withCount('children')
                ->where('slug', $slug)
                ->firstOrFail();

            $relatedSpots = Spot::query()
                ->visible()
                ->with(['genres', 'tags', 'media'])
                ->withCount('children')
                ->whereKeyNot($spot->id)
                ->where(fn (Builder $query) => $query
                    ->where('prefecture', $spot->prefecture)
                )
                ->limit(6)
                ->get();
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            abort(503, 'データベース未初期化のため、スポット詳細はまだ利用できません。');
        }

        return view('spots.show', compact('spot', 'relatedSpots'));
    }

    private function applyAreaFilter(\Illuminate\Database\Query\Builder $query, string $area): void
    {
        if (preg_match('/^\[駅\]\s*(.+?)(?:（.+?）)?$/', $area, $m)) {
            $stationIds = $this->stationAndNearbyIds(trim($m[1]));
            if ($stationIds->isNotEmpty()) {
                $query->whereIn('id', function ($sub) use ($stationIds) {
                    $sub->select('spot_id')
                        ->from('spot_stations')
                        ->whereIn('station_id', $stationIds);
                });
            } else {
                $query->whereRaw('0=1');
            }
        } elseif (preg_match('/^\[路線\]\s*(.+?)(?:（.+?）)?$/', $area, $m)) {
            $stationIds = $this->routeStationIds(trim($m[1]));
            if ($stationIds->isNotEmpty()) {
                $query->whereIn('id', function ($sub) use ($stationIds) {
                    $sub->select('spot_id')
                        ->from('spot_stations')
                        ->whereIn('station_id', $stationIds);
                });
            } else {
                $query->whereRaw('0=1');
            }
        } else {
            $query->where(fn ($b) => $b
                ->where('prefecture', 'like', "%{$area}%")
                ->orWhere('city', 'like', "%{$area}%")
                ->orWhere('town', 'like', "%{$area}%")
            );
        }
    }

    /** @return Collection<int, int> */
    private function stationAndNearbyIds(string $stationName): Collection
    {
        $baseIds = Station::query()
            ->where('station_name', 'like', "%{$stationName}%")
            ->pluck('id');

        if ($baseIds->isEmpty()) {
            return collect();
        }

        $nearbyIds = StationNearStation::query()
            ->whereIn('station_id', $baseIds)
            ->where('walking_minutes', '<=', 15)
            ->pluck('near_station_id');

        return $baseIds->merge($nearbyIds)->unique()->values();
    }

    /** @return Collection<int, int> */
    private function routeStationIds(string $lineName): Collection
    {
        return RailwayRoute::query()
            ->where('line_name', 'like', "%{$lineName}%")
            ->get()
            ->flatMap(fn (RailwayRoute $route) => $route->stations()->pluck('stations.id'))
            ->unique()
            ->values();
    }
}
