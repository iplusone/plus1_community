<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use App\Models\SpotMedia;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        $this->ensureTypeLimit($spot, $data['type']);
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
        $data = $this->validated($request);
        $this->ensureTypeLimit($spot, $data['type'], $medium);
        $medium->update($data);

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
        $data = $request->validate([
            'type' => ['required', 'string', 'in:image,video'],
            'path' => ['nullable', 'string', 'max:2000'],
            'thumbnail_path' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'uploaded_image' => ['nullable', 'image', 'max:5120'],
        ]) + ['sort_order' => (int) $request->input('sort_order', 0)];

        if ($data['type'] === 'video') {
            if (blank($data['path'] ?? null)) {
                throw ValidationException::withMessages([
                    'path' => '動画はYouTubeの埋め込みタグを入力してください。',
                ]);
            }

            $data['path'] = $this->normalizeYoutubeEmbed($data['path']);
            $data['thumbnail_path'] = null;

            return $this->stripUploadOnlyFields($data);
        }

        if ($request->file('uploaded_image') instanceof UploadedFile) {
            $storedPath = $request->file('uploaded_image')->store('spot-media', 'public');
            $data['path'] = $storedPath;
            $data['thumbnail_path'] = $storedPath;
        }

        if (blank($data['path'] ?? null)) {
            throw ValidationException::withMessages([
                'uploaded_image' => '画像ファイルを追加するか、画像URLを入力してください。',
            ]);
        }

        return $this->stripUploadOnlyFields($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function stripUploadOnlyFields(array $data): array
    {
        unset($data['uploaded_image']);

        return $data;
    }

    private function ensureTypeLimit(Spot $spot, string $type, ?SpotMedia $ignore = null): void
    {
        $count = $spot->media()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('type', $type)
            ->count();

        $limit = $type === 'video' ? 5 : 10;

        if ($count >= $limit) {
            throw ValidationException::withMessages([
                'type' => $type === 'video'
                    ? '動画は5件まで登録できます。'
                    : '画像は10枚まで登録できます。',
            ]);
        }
    }

    private function normalizeYoutubeEmbed(string $value): string
    {
        $value = trim($value);

        if (preg_match('/src=["\']([^"\']+)["\']/i', $value, $matches) !== 1) {
            throw ValidationException::withMessages([
                'path' => '動画はYouTubeの埋め込みタグを入力してください。',
            ]);
        }

        $src = trim($matches[1]);

        if (! Str::startsWith($src, ['https://www.youtube.com/embed/', 'https://www.youtube-nocookie.com/embed/'])) {
            throw ValidationException::withMessages([
                'path' => 'YouTubeの埋め込みタグのみ登録できます。',
            ]);
        }

        return $src;
    }
}
