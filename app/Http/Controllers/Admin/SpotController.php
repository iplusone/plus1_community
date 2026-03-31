<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SpotController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $spots = $this->spotIndexQuery($request)
                ->with('parent')
                ->orderBy('depth')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
            $dbWarning = null;
        } catch (\Throwable $e) {
            $spots = new LengthAwarePaginator([], 0, 20);
            $dbWarning = 'データベース未初期化のため、スポット管理一覧はまだ空です。';
        }

        return view('admin.spots.index', [
            'spots' => $spots,
            'dbWarning' => $dbWarning,
            'filters' => $request->only(['name', 'prefecture', 'city', 'is_public']),
        ]);
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
        $spot->company_id = $this->resolveCompanyId($spot, $spot->parent_id);
        $this->ensureValidParentSelection($spot, $spot->parent_id);
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
        $spot->company_id = $this->resolveCompanyId($spot, $spot->parent_id);
        $this->ensureValidParentSelection($spot, $spot->parent_id);
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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
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

        return ($parent?->depth ?? 0) + 1;
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
            $query = Spot::query()
                ->with(['parent.parent.parent.parent'])
                ->when($ignoreId, fn ($builder) => $builder->whereKeyNot($ignoreId));

            $user = $this->currentUser();
            $editingSpot = $ignoreId ? Spot::query()->find($ignoreId) : null;
            $companyId = $editingSpot?->company_id ?? $user?->company_id;

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            if ($user?->company_id) {
                $manageableIds = $user->manageableSpotIds();

                if ($manageableIds !== []) {
                    $query->whereIn('id', $manageableIds);
                }
            }

            if ($editingSpot) {
                $query->whereNotIn('id', $editingSpot->descendantIds());
            }

            $parents = $query
                ->where('depth', '<', 5)
                ->orderBy('name')
                ->get();

            return [$parents, null];
        } catch (\Throwable $e) {
            return [new Collection(), 'データベース未初期化のため、親スポット候補はまだ取得できません。'];
        }
    }

    private function spotIndexQuery(Request $request)
    {
        $query = Spot::query();
        $user = $this->currentUser();

        if ($user?->company_id) {
            $query->where('company_id', $user->company_id);

            $manageableIds = $user->manageableSpotIds();

            if ($manageableIds !== []) {
                $query->whereIn('id', $manageableIds);
            }
        }

        $name = trim((string) $request->string('name'));
        $prefecture = trim((string) $request->string('prefecture'));
        $city = trim((string) $request->string('city'));
        $isPublic = $request->string('is_public')->value();

        if ($name !== '') {
            $query->where('name', 'like', "%{$name}%");
        }

        if ($prefecture !== '') {
            $query->where('prefecture', 'like', "%{$prefecture}%");
        }

        if ($city !== '') {
            $query->where('city', 'like', "%{$city}%");
        }

        if (in_array($isPublic, ['1', '0'], true)) {
            $query->where('is_public', $isPublic === '1');
        }

        return $query;
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private function resolveCompanyId(Spot $spot, ?int $parentId): ?int
    {
        if ($parentId) {
            return Spot::query()->whereKey($parentId)->value('company_id')
                ?: $spot->company_id
                ?: $this->currentUser()?->company_id;
        }

        return $spot->company_id ?: $this->currentUser()?->company_id;
    }

    private function ensureValidParentSelection(Spot $spot, ?int $parentId): void
    {
        if (! $parentId) {
            return;
        }

        $parent = Spot::query()->find($parentId);

        if (! $parent) {
            return;
        }

        $errors = [];

        if ($spot->exists && $parent->id === $spot->id) {
            $errors['parent_id'] = '自分自身を親スポットには設定できません。';
        }

        if ($spot->exists && in_array($parent->id, $spot->descendantIds(), true)) {
            $errors['parent_id'] = '配下のスポットは親スポットに設定できません。';
        }

        if ($spot->company_id && $parent->company_id && $spot->company_id !== $parent->company_id) {
            $errors['parent_id'] = '別組織のスポットは親スポットに設定できません。';
        }

        if (($parent->depth + 1) > 5) {
            $errors['parent_id'] = '親スポットを設定すると最大5階層を超えるため保存できません。';
        }

        $user = $this->currentUser();

        if ($user?->company_id && $parent->company_id !== $user->company_id) {
            $errors['parent_id'] = '同じ組織のスポットのみ親スポットに設定できます。';
        }

        if ($user?->company_id) {
            $manageableIds = $user->manageableSpotIds();

            if ($manageableIds !== [] && ! in_array($parent->id, $manageableIds, true)) {
                $errors['parent_id'] = '管理範囲外のスポットは親スポットに設定できません。';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
