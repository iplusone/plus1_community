<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prefecture;
use App\Models\RailwayRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StationPickerController extends Controller
{
    public function prefectures(): JsonResponse
    {
        $prefectures = Prefecture::query()
            ->orderBy('code')
            ->get(['id', 'name', 'code', 'region']);

        $regions = $prefectures
            ->groupBy('region')
            ->map(fn ($prefs, $region) => [
                'name' => $region,
                'prefectures' => $prefs->values(),
            ])
            ->values();

        return response()->json($regions);
    }

    public function railways(Request $request): JsonResponse
    {
        $prefCode = $request->query('pref_code');

        $routes = RailwayRoute::query()
            ->whereRaw('FIND_IN_SET(?, pref_codes)', [$prefCode])
            ->orderBy('line_name')
            ->get(['id', 'line_name', 'operator_name']);

        return response()->json($routes);
    }

    public function stations(Request $request): JsonResponse
    {
        $routeId = $request->query('railway_route_id');

        $stations = RailwayRoute::findOrFail($routeId)
            ->stations()
            ->get(['stations.id', 'stations.station_name']);

        return response()->json($stations);
    }
}
