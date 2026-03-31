<?php

namespace App\Http\Controllers;

use App\Models\Spot;
use App\Models\SpotFeaturedSlot;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $sections = [
            'featuredSpots' => collect(),
            'latestSpots' => collect(),
            'randomSpots' => collect(),
            'stats' => [
                'total_spots' => 0,
                'public_spots' => 0,
            ],
            'dbWarning' => null,
        ];

        try {
            $baseQuery = Spot::query()
                ->visible()
                ->with(['genres', 'tags', 'media'])
                ->withCount('children');

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
                ->take(10)
                ->values();

            if ($sections['featuredSpots']->isEmpty()) {
                $sections['featuredSpots'] = (clone $baseQuery)
                    ->orderByDesc('view_count')
                    ->limit(10)
                    ->get();
            }

            $sections['latestSpots'] = (clone $baseQuery)
                ->orderByDesc('published_at')
                ->limit(10)
                ->get();

            $sections['randomSpots'] = (clone $baseQuery)
                ->inRandomOrder()
                ->limit(10)
                ->get();

            $sections['stats'] = [
                'total_spots' => Spot::count(),
                'public_spots' => (clone $baseQuery)->count(),
            ];
        } catch (\Throwable $e) {
            $sections['dbWarning'] = 'データベース未初期化のため、公開スポット一覧はまだ空です。';
        }

        return view('home', $sections);
    }
}
