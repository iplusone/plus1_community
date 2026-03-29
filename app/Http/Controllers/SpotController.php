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

        try {
            $query = Spot::query()->visible()->with(['genres', 'tags'])->orderByDesc('published_at');

            $this->applyFilters($query, $request);

            $spots = $query->paginate(12)->withQueryString();
        } catch (\Throwable $e) {
            $dbWarning = 'データベース未初期化のため、スポット検索はまだ利用できません。';
        }

        return view('spots.index', [
            'spots' => $spots,
            'filters' => $request->only(['q', 'prefecture', 'genre', 'tag']),
            'dbWarning' => $dbWarning,
        ]);
    }

    public function show(string $slug): View
    {
        try {
            $spot = Spot::query()
                ->visible()
                ->with(['children', 'genres', 'tags', 'businessHours', 'services.menus', 'media', 'staff', 'coupons'])
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
}
