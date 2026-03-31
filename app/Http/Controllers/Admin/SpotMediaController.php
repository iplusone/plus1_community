<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spot;
use App\Models\SpotMedia;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SpotMediaController extends Controller
{
    public function index(Request $request, Spot $spot): View
    {
        $type = $this->mediaType($request);
        $media = $spot->media()
            ->where('type', $type)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $allMedia = $spot->media()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.spots.media.index', [
            'spot' => $spot,
            'media' => $media,
            'allMedia' => $allMedia,
            'mediaType' => $type,
        ]);
    }

    public function create(Request $request, Spot $spot): View
    {
        $mediaType = $this->mediaType($request);

        return view('admin.spots.media.form', [
            'spot' => $spot,
            'item' => new SpotMedia(),
            'formAction' => route('admin.spots.media.store', $spot),
            'formMethod' => 'POST',
            'mediaType' => $mediaType,
        ]);
    }

    public function store(Request $request, Spot $spot): RedirectResponse
    {
        $mediaType = $this->mediaType($request);

        if ($mediaType === 'image') {
            $createdCount = $this->storeImages($request, $spot);

            return redirect()->route('admin.spots.media.index', ['spot' => $spot, 'type' => 'image'])
                ->with('status', $createdCount > 1 ? "{$createdCount}件の画像を追加しました。" : '画像を追加しました。');
        }

        $data = $this->validatedVideo($request);
        $this->ensureTypeLimit($spot, 'video');
        $spot->media()->create($data);

        return redirect()->route('admin.spots.media.index', ['spot' => $spot, 'type' => 'video'])
            ->with('status', '動画を追加しました。');
    }

    public function edit(Request $request, Spot $spot, SpotMedia $medium): View
    {
        return view('admin.spots.media.form', [
            'spot' => $spot,
            'item' => $medium,
            'formAction' => route('admin.spots.media.update', [$spot, $medium]),
            'formMethod' => 'PUT',
            'mediaType' => $medium->type,
        ]);
    }

    public function update(Request $request, Spot $spot, SpotMedia $medium): RedirectResponse
    {
        $data = $medium->type === 'image'
            ? $this->validatedSingleImage($request)
            : $this->validatedVideo($request);

        $this->ensureTypeLimit($spot, $medium->type, $medium);
        $medium->update($data);

        return redirect()->route('admin.spots.media.index', ['spot' => $spot, 'type' => $medium->type])
            ->with('status', $medium->type === 'image' ? '画像を更新しました。' : '動画を更新しました。');
    }

    public function destroy(Spot $spot, SpotMedia $medium): RedirectResponse
    {
        $medium->delete();

        return redirect()->route('admin.spots.media.index', ['spot' => $spot, 'type' => $medium->type])
            ->with('status', $medium->type === 'image' ? '画像を削除しました。' : '動画を削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSingleImage(Request $request): array
    {
        $data = $request->validate([
            'path' => ['nullable', 'string', 'max:2000'],
            'thumbnail_path' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'uploaded_image' => ['nullable', 'image', 'max:5120'],
        ]) + ['sort_order' => (int) $request->input('sort_order', 0), 'type' => 'image'];

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
     * @return array<string, mixed>
     */
    private function validatedVideo(Request $request): array
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:5000'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + ['sort_order' => (int) $request->input('sort_order', 0), 'type' => 'video'];

        $data['path'] = $this->normalizeYoutubeEmbed($data['path']);
        $data['thumbnail_path'] = null;

        return $data;
    }

    private function storeImages(Request $request, Spot $spot): int
    {
        $rawUploads = $request->file('uploaded_images', []);

        if ($rawUploads === [] && $request->file('uploaded_image') instanceof UploadedFile) {
            $rawUploads = [$request->file('uploaded_image')];
        }

        $uploadedImages = collect($rawUploads)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->values();

        $validated = $request->validate([
            'uploaded_images' => ['nullable', 'array'],
            'uploaded_images.*' => ['image', 'max:5120'],
            'uploaded_image' => ['nullable', 'image', 'max:5120'],
            'path' => ['nullable', 'string', 'max:2000'],
            'thumbnail_path' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($uploadedImages->isEmpty() && blank($validated['path'] ?? null)) {
            throw ValidationException::withMessages([
                'uploaded_images' => '画像を1枚以上追加するか、画像URLを入力してください。',
            ]);
        }

        $existingCount = $spot->media()->where('type', 'image')->count();
        $incomingCount = max($uploadedImages->count(), blank($validated['path'] ?? null) ? 0 : 1);

        if ($existingCount + $incomingCount > 10) {
            throw ValidationException::withMessages([
                'type' => '画像は10枚まで登録できます。',
            ]);
        }

        $sortOrder = (int) ($validated['sort_order'] ?? 0);
        $caption = trim((string) ($validated['caption'] ?? ''));
        $createdCount = 0;

        /** @var Collection<int, UploadedFile> $uploadedImages */
        foreach ($uploadedImages as $index => $uploadedImage) {
            $storedPath = $uploadedImage->store('spot-media', 'public');

            $spot->media()->create([
                'type' => 'image',
                'path' => $storedPath,
                'thumbnail_path' => $storedPath,
                'caption' => $caption !== '' ? $caption : null,
                'sort_order' => $sortOrder + $index,
            ]);

            $createdCount++;
        }

        if ($uploadedImages->isEmpty() && ! blank($validated['path'] ?? null)) {
            $spot->media()->create([
                'type' => 'image',
                'path' => trim((string) $validated['path']),
                'thumbnail_path' => blank($validated['thumbnail_path'] ?? null) ? trim((string) $validated['path']) : trim((string) $validated['thumbnail_path']),
                'caption' => $caption !== '' ? $caption : null,
                'sort_order' => $sortOrder,
            ]);

            $createdCount++;
        }

        return $createdCount;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function stripUploadOnlyFields(array $data): array
    {
        unset($data['uploaded_image']);
        unset($data['uploaded_images']);

        return $data;
    }

    private function mediaType(Request $request): string
    {
        return $request->string('type')->value() === 'video' ? 'video' : 'image';
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
