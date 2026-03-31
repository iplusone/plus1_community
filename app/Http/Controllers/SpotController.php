<?php

namespace App\Http\Controllers;

use App\Models\RailwayRoute;
use App\Models\Spot;
use App\Models\Station;
use App\Models\StationNearStation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SpotController extends Controller
{
    public function index(Request $request): View
    {
        $spots = collect();
        $dbWarning = null;
        $sort = $request->string('sort')->value() ?: 'latest';
        $view = $request->string('view')->value() ?: 'card';

        try {
            $query = Spot::query()
                ->visible()
                ->with(['genres', 'tags', 'media'])
                ->withCount('children');

            $this->applyFilters($query, $request);
            $this->applySorting($query, $sort);

            $spots = $query->paginate(50)->withQueryString();
        } catch (\Throwable $e) {
            $dbWarning = 'データベース未初期化のため、スポット検索はまだ利用できません。';
        }

        return view('spots.index', [
            'spots' => $spots,
            'filters' => $request->only(['q', 'area', 'genre', 'tag', 'sort', 'view']),
            'sort' => in_array($sort, ['latest', 'popular'], true) ? $sort : 'latest',
            'viewMode' => in_array($view, ['card', 'list'], true) ? $view : 'card',
            'dbWarning' => $dbWarning,
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
                ->where(function (Builder $query) use ($spot) {
                    $query->where('prefecture', $spot->prefecture);

                    if ($spot->genres->isNotEmpty()) {
                        $query->orWhereHas('genres', fn (Builder $builder) => $builder->whereIn('genres.id', $spot->genres->pluck('id')));
                    }
                })
                ->limit(6)
                ->get();
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            abort(503, 'データベース未初期化のため、スポット詳細はまだ利用できません。');
        }

        return view('spots.show', compact('spot', 'relatedSpots'));
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $q = trim((string) $request->string('q'));
        $area = trim((string) $request->string('area'));
        $genre = trim((string) $request->string('genre'));
        $tag = trim((string) $request->string('tag'));

        if ($q !== '') {
            $query->where(function (Builder $builder) use ($q) {
                $builder
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('prefecture', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('town', 'like', "%{$q}%");
            });
        }

        if ($area !== '') {
            if (preg_match('/^\[駅\]\s*(.+?)(?:（.+?）)?$/', $area, $m)) {
                $stationName = trim($m[1]);
                $stationIds = $this->stationAndNearbyIds($stationName);
                $query->whereHas('spotStations', fn (Builder $b) => $b->whereIn('station_id', $stationIds));
            } elseif (preg_match('/^\[路線\]\s*(.+?)(?:（.+?）)?$/', $area, $m)) {
                $lineName = trim($m[1]);
                $stationIds = $this->routeStationIds($lineName);
                $query->whereHas('spotStations', fn (Builder $b) => $b->whereIn('station_id', $stationIds));
            } else {
                $query->where(function (Builder $builder) use ($area) {
                    $builder
                        ->where('prefecture', 'like', "%{$area}%")
                        ->orWhere('city', 'like', "%{$area}%")
                        ->orWhere('town', 'like', "%{$area}%");
                });
            }
        }

        if ($genre !== '') {
            $query->whereHas('genres', fn (Builder $builder) => $builder->where('name', 'like', "%{$genre}%"));
        }

        if ($tag !== '') {
            $query->whereHas('tags', fn (Builder $builder) => $builder->where('name', 'like', "%{$tag}%"));
        }
    }

    private function applySorting(Builder $query, string $sort): void
    {
        if ($sort === 'popular') {
            $query->orderByDesc('view_count')->orderByDesc('published_at');

            return;
        }

        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * @return Collection<int, int>
     */
    private function stationAndNearbyIds(string $stationName): Collection
    {
        $baseStationIds = Station::query()
            ->where('station_name', 'like', "%{$stationName}%")
            ->pluck('id');

        if ($baseStationIds->isEmpty()) {
            return collect();
        }

        $nearbyStationIds = StationNearStation::query()
            ->whereIn('station_id', $baseStationIds)
            ->where('walking_minutes', '<=', 15)
            ->pluck('near_station_id');

        return $baseStationIds
            ->merge($nearbyStationIds)
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
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
