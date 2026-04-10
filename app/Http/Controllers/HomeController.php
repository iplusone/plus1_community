<?php

namespace App\Http\Controllers;

use App\Models\Spot;
use App\Models\SpotFeaturedSlot;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    private const PRIORITY_PREFECTURE = '千葉県';

    public function index(): View
    {
        $baseQuery = Spot::query()
            ->visible()
            ->with(['genres', 'tags', 'media'])
            ->withCount('children');

        $sections = [
            'priorityPrefecture' => self::PRIORITY_PREFECTURE,
            'prioritySpots' => collect(),
            'featuredSpots' => collect(),
            'latestSpots' => collect(),
            'randomSpots' => collect(),
            'stats' => $this->loadStats($baseQuery),
            'dbWarning' => null,
        ];

        try {
            $sections['prioritySpots'] = (clone $baseQuery)
                ->where('prefecture', self::PRIORITY_PREFECTURE)
                ->orderByDesc('published_at')
                ->orderByDesc('view_count')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            report($e);
            $sections['dbWarning'] = '一部のトップ表示を読み込めませんでした。';
        }

        try {
            $sections['featuredSpots'] = SpotFeaturedSlot::query()
                ->where('slot_type', 'featured')
                ->where(function ($query) {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->with(['spot' => fn ($query) => $query->visible()->with(['genres', 'tags', 'media'])->withCount('children')])
                ->orderBy('sort_order')
                ->get();
            $sections['featuredSpots'] = $sections['featuredSpots']
                ->pluck('spot')
                ->filter()
                ->sortByDesc(fn (Spot $spot) => $spot->prefecture === self::PRIORITY_PREFECTURE)
                ->take(10)
                ->values();

            if ($sections['featuredSpots']->isEmpty()) {
                $sections['featuredSpots'] = $this->applyPriorityPrefectureOrdering(clone $baseQuery)
                    ->orderByDesc('view_count')
                    ->orderByDesc('published_at')
                    ->limit(10)
                    ->get();
            }
        } catch (\Throwable $e) {
            report($e);
            $sections['dbWarning'] = '一部のトップ表示を読み込めませんでした。';
        }

        try {
            $sections['latestSpots'] = $this->applyPriorityPrefectureOrdering(clone $baseQuery)
                ->orderByDesc('published_at')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            report($e);
            $sections['dbWarning'] = '一部のトップ表示を読み込めませんでした。';
        }

        try {
            $sections['randomSpots'] = $this->applyPriorityPrefectureOrdering(clone $baseQuery)
                ->inRandomOrder('id')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            report($e);
            $sections['dbWarning'] = '一部のトップ表示を読み込めませんでした。';
        }

        return view('home', $sections);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<Spot> $baseQuery
     * @return array{total_spots:int, public_spots:int}
     */
    private function loadStats(\Illuminate\Database\Eloquent\Builder $baseQuery): array
    {
        try {
            return [
                'total_spots' => Spot::query()->count(),
                'public_spots' => (clone $baseQuery)->count(),
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'total_spots' => 0,
                'public_spots' => 0,
            ];
        }
    }

    private function applyPriorityPrefectureOrdering(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderByRaw(
            'CASE WHEN prefecture = ? THEN 0 ELSE 1 END',
            [self::PRIORITY_PREFECTURE]
        );
    }
}
