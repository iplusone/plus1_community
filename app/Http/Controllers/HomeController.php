<?php

namespace App\Http\Controllers;

use App\Models\Spot;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

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
                ->with(['genres', 'tags'])
                ->withCount('children');

            $sections['featuredSpots'] = (clone $baseQuery)
                ->orderByDesc('view_count')
                ->limit(10)
                ->get();

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
