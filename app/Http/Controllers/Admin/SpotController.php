<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SpotController extends Controller
{
    public function index(): View
    {
        try {
            $spots = Spot::query()
                ->with('parent')
                ->orderBy('depth')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20);
            $dbWarning = null;
        } catch (\Throwable $e) {
            $spots = new LengthAwarePaginator([], 0, 20);
            $dbWarning = 'データベース未初期化のため、スポット管理一覧はまだ空です。';
        }

        return view('admin.spots.index', compact('spots', 'dbWarning'));
    }

    public function create(): View
    {
        [$parents, $dbWarning] = $this->parentOptions();

        return view('admin.spots.form', [
            'spot' => new Spot(),
            'parents' => $parents,
            'formAction' => route('admin.spots.store'),
            'formMethod' => 'POST',
            'dbWarning' => $dbWarning,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $spot = new Spot();
        $spot->fill($this->validatedData($request));
        $spot->slug = $this->uniqueSlug($request->input('slug'), $spot->name);
        $spot->depth = $this->resolveDepth($spot->parent_id);
        $spot->save();

        return redirect()->route('admin.spots.index')->with('status', 'スポットを作成しました。');
    }

    public function edit(Spot $spot): View
    {
        [$parents, $dbWarning] = $this->parentOptions($spot->id);

        return view('admin.spots.form', [
            'spot' => $spot,
            'parents' => $parents,
            'formAction' => route('admin.spots.update', $spot),
            'formMethod' => 'PUT',
            'dbWarning' => $dbWarning,
        ]);
    }

    public function update(Request $request, Spot $spot): RedirectResponse
    {
        $spot->fill($this->validatedData($request));
        $spot->slug = $this->uniqueSlug($request->input('slug'), $spot->name, $spot->id);
        $spot->depth = $this->resolveDepth($spot->parent_id);
        $spot->save();

        return redirect()->route('admin.spots.index')->with('status', 'スポットを更新しました。');
    }

    public function destroy(Spot $spot): RedirectResponse
    {
        $spot->delete();

        return redirect()->route('admin.spots.index')->with('status', 'スポットを削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'parent_id' => ['nullable', 'exists:spots,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'prefecture' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string'],
            'access_text' => ['nullable', 'string'],
            'business_hours_text' => ['nullable', 'string'],
            'holiday_text' => ['nullable', 'string'],
            'thumbnail_path' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'is_public' => $request->boolean('is_public'),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }

    private function resolveDepth(?int $parentId): int
    {
        if (! $parentId) {
            return 1;
        }

        $parent = Spot::query()->find($parentId);

        return min(($parent?->depth ?? 0) + 1, 5);
    }

    private function uniqueSlug(?string $slugInput, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slugInput ?: $name);
        $base = $base !== '' ? $base : 'spot';
        $slug = $base;
        $counter = 1;

        while (
            Spot::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return array{0: Collection<int, Spot>, 1: ?string}
     */
    private function parentOptions(?int $ignoreId = null): array
    {
        try {
            $parents = Spot::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->orderBy('name')
                ->get();

            return [$parents, null];
        } catch (\Throwable $e) {
            return [new Collection(), 'データベース未初期化のため、親スポット候補はまだ取得できません。'];
        }
    }
}
