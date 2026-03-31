<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\RailwayRoute;
use App\Models\Spot;
use App\Models\Station;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchSuggestionController extends Controller
{
    public function genres(Request $request): JsonResponse
    {
        return response()->json([
            'items' => $this->simpleSuggestions(Genre::class, $request->string('q')->value()),
        ]);
    }

    public function tags(Request $request): JsonResponse
    {
        return response()->json([
            'items' => $this->simpleSuggestions(Tag::class, $request->string('q')->value()),
        ]);
    }

    public function area(Request $request): JsonResponse
    {
        $keyword = trim($request->string('q')->value());

        if ($keyword === '') {
            return response()->json(['items' => []]);
        }

        try {
            $items = collect()
                ->merge($this->addressSuggestions($keyword))
                ->merge($this->stationSuggestions($keyword))
                ->merge($this->routeSuggestions($keyword))
                ->unique()
                ->values()
                ->all();

            return response()->json(['items' => $items]);
        } catch (\Throwable $e) {
            return response()->json(['items' => []]);
        }
    }

    /**
     * @return list<string>
     */
    private function addressSuggestions(string $keyword): array
    {
        return Spot::query()
            ->where(function ($q) use ($keyword) {
                $q->where('prefecture', 'like', "%{$keyword}%")
                    ->orWhere('city', 'like', "%{$keyword}%")
                    ->orWhere('town', 'like', "%{$keyword}%");
            })
            ->get(['prefecture', 'city', 'town'])
            ->flatMap(fn ($s) => array_filter([$s->prefecture, $s->city, $s->town]))
            ->filter(fn (string $v) => mb_stripos($v, $keyword) !== false)
            ->unique()
            ->sort()
            ->take(4)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function stationSuggestions(string $keyword): array
    {
        return Station::query()
            ->where('station_name', 'like', "%{$keyword}%")
            ->orderBy('station_name')
            ->limit(5)
            ->get(['station_name', 'operator_name'])
            ->map(fn ($s) => '[駅] ' . $s->station_name . ($s->operator_name ? '（' . $s->operator_name . '）' : ''))
            ->all();
    }

    /**
     * @return list<string>
     */
    private function routeSuggestions(string $keyword): array
    {
        return RailwayRoute::query()
            ->where('line_name', 'like', "%{$keyword}%")
            ->orWhere('operator_name', 'like', "%{$keyword}%")
            ->orderBy('line_name')
            ->limit(4)
            ->get(['line_name', 'operator_name'])
            ->map(fn ($r) => '[路線] ' . $r->line_name . ($r->operator_name ? '（' . $r->operator_name . '）' : ''))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function simpleSuggestions(string $modelClass, string $keyword): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [];
        }

        try {
            /** @var \Illuminate\Database\Eloquent\Model $modelClass */
            return $modelClass::query()
                ->where('name', 'like', "%{$keyword}%")
                ->orderBy('name')
                ->limit(8)
                ->pluck('name')
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
