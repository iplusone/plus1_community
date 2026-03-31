@extends('layouts.app')

@section('title', $item->exists ? 'メディア編集' : 'メディア追加')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>{{ $item->exists ? 'メディア編集' : 'メディア追加' }}</h1>
            </div>
        </div>

        <div class="notice-panel compact">
            <p>画像は10枚まで、動画はYouTubeの埋め込みタグで5件まで登録できます。</p>
        </div>

        <form method="POST" action="{{ $formAction }}" class="form-card" enctype="multipart/form-data">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            @php
                $currentType = old('type', $item->type ?: 'image');
            @endphp

            <label>
                <span>種別</span>
                <select name="type" id="media-type-select">
                    <option value="image" @selected($currentType === 'image')>画像</option>
                    <option value="video" @selected($currentType === 'video')>動画</option>
                </select>
            </label>

            <div class="media-mode-panel" data-media-mode="image" @style([$currentType !== 'image' ? 'display:none' : null])>
                <div class="media-upload" id="image-dropzone" tabindex="0" role="button" aria-label="画像をドラッグ&ドロップまたは選択">
                    <input type="file" name="uploaded_image" id="uploaded-image-input" accept="image/*" hidden>
                    <div class="media-upload__copy">
                        <strong>画像をドラッグ&ドロップ</strong>
                        <span>またはクリックして画像を選択</span>
                        <small>JPG / PNG / WebP / GIF, 5MBまで</small>
                    </div>
                    <div class="media-upload__preview" id="image-preview" @style([! $item->thumbnailUrl() ? 'display:none' : null])>
                        @if ($item->thumbnailUrl())
                            <img src="{{ $item->thumbnailUrl() }}" alt="{{ $item->caption ?: $spot->name }}">
                        @endif
                    </div>
                </div>

                <label>
                    <span>画像URL / ストレージパス</span>
                    <input type="text" name="path" id="image-path-input" value="{{ old('path', $currentType === 'image' ? $item->path : '') }}">
                    <small>アップロードの代わりにURLや既存ストレージパスを指定することもできます。</small>
                </label>

                <label>
                    <span>サムネイルパス</span>
                    <input type="text" name="thumbnail_path" value="{{ old('thumbnail_path', $currentType === 'image' ? $item->thumbnail_path : '') }}">
                </label>
            </div>

            <div class="media-mode-panel" data-media-mode="video" @style([$currentType !== 'video' ? 'display:none' : null])>
                <label>
                    <span>YouTube埋め込みタグ</span>
                    <textarea name="path" rows="5" placeholder='<iframe src="https://www.youtube.com/embed/..."></iframe>'>{{ old('path', $currentType === 'video' ? $item->path : '') }}</textarea>
                    <small>YouTubeの埋め込みタグをそのまま貼り付けてください。</small>
                </label>
            </div>

            <label>
                <span>キャプション</span>
                <input type="text" name="caption" value="{{ old('caption', $item->caption) }}">
            </label>

            <label>
                <span>表示順</span>
                <input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}" min="0">
            </label>

            @if ($errors->any())
                <div class="notice-panel compact error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="hero-actions">
                <button type="submit" class="button-primary">{{ $item->exists ? '更新する' : '追加する' }}</button>
                <a class="button-secondary" href="{{ route('admin.spots.media.index', $spot) }}">一覧へ戻る</a>
            </div>
        </form>
    </section>
@endsection
