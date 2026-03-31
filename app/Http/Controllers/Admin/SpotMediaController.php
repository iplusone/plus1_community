<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use App\Models\SpotMedia;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpotMediaController extends Controller
{
    public function index(Spot $spot): View
    {
        $media = $spot->media()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.spots.media.index', compact('spot', 'media'));
    }

    public function create(Spot $spot): View
    {
        return view('admin.spots.media.form', [
            'spot' => $spot,
            'item' => new SpotMedia(),
            'formAction' => route('admin.spots.media.store', $spot),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request, Spot $spot): RedirectResponse
    {
        $data = $this->validated($request);
        $spot->media()->create($data);

        return redirect()->route('admin.spots.media.index', $spot)
            ->with('status', 'メディアを追加しました。');
    }

    public function edit(Spot $spot, SpotMedia $medium): View
    {
        return view('admin.spots.media.form', [
            'spot' => $spot,
            'item' => $medium,
            'formAction' => route('admin.spots.media.update', [$spot, $medium]),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Spot $spot, SpotMedia $medium): RedirectResponse
    {
        $medium->update($this->validated($request));

        return redirect()->route('admin.spots.media.index', $spot)
            ->with('status', 'メディアを更新しました。');
    }

    public function destroy(Spot $spot, SpotMedia $medium): RedirectResponse
    {
        $medium->delete();

        return redirect()->route('admin.spots.media.index', $spot)
            ->with('status', 'メディアを削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'string', 'in:image,video'],
            'path' => ['required', 'string', 'max:255'],
            'thumbnail_path' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + ['sort_order' => (int) $request->input('sort_order', 0)];
    }
}
