<?php

namespace App\Http\Controllers;

use App\Models\Spot;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
                ->with(['genres', 'tags'])
                ->withCount('children');

            $this->applyFilters($query, $request);
            $this->applySorting($query, $sort);

            $spots = $query->paginate(50)->withQueryString();
        } catch (\Throwable $e) {
            $dbWarning = 'データベース未初期化のため、スポット検索はまだ利用できません。';
        }

        return view('spots.index', [
            'spots' => $spots,
            'filters' => $request->only(['q', 'prefecture', 'genre', 'tag', 'sort', 'view']),
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
                ])
                ->where('slug', $slug)
                ->firstOrFail();
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            abort(503, 'データベース未初期化のため、スポット詳細はまだ利用できません。');
        }

        return view('spots.show', compact('spot'));
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $q = trim((string) $request->string('q'));
        $prefecture = trim((string) $request->string('prefecture'));
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

        if ($prefecture !== '') {
            $query->where('prefecture', 'like', "%{$prefecture}%");
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
}
