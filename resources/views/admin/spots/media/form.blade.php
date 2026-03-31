@extends('layouts.app')

@section('title', $item->exists ? 'メディア編集' : 'メディア追加')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="admin-tabs">
            <a href="{{ route('admin.spots.media.index', ['spot' => $spot, 'type' => 'image']) }}"
               @class(['is-active' => $mediaType === 'image'])>画像</a>
            <a href="{{ route('admin.spots.media.index', ['spot' => $spot, 'type' => 'video']) }}"
               @class(['is-active' => $mediaType === 'video'])>動画</a>
        </div>

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>{{ $item->exists ? ($mediaType === 'image' ? '画像編集' : '動画編集') : ($mediaType === 'image' ? '画像追加' : '動画追加') }}</h1>
            </div>
        </div>

        <div class="notice-panel compact">
            <p>{{ $mediaType === 'image' ? '画像は10枚まで登録できます。' : '動画はYouTubeの埋め込みタグで5件まで登録できます。' }}</p>
        </div>

        <form method="POST" action="{{ $formAction }}" class="form-card" enctype="multipart/form-data">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <input type="hidden" name="type" value="{{ $mediaType }}">

            @if ($mediaType === 'image')
                <div class="media-upload" id="image-dropzone" tabindex="0" role="button" aria-label="画像をドラッグ&ドロップまたは選択">
                    <input type="file" name="{{ $item->exists ? 'uploaded_image' : 'uploaded_images[]' }}" id="uploaded-image-input" accept="image/*" @if (! $item->exists) multiple @endif hidden>
                    <div class="media-upload__copy">
                        <strong>{{ $item->exists ? '画像をドラッグ&ドロップ' : '画像をまとめてドラッグ&ドロップ' }}</strong>
                        <span>{{ $item->exists ? 'またはクリックして画像を選択' : 'またはクリックして複数画像を選択' }}</span>
                        <small>JPG / PNG / WebP / GIF, 5MBまで</small>
                    </div>
                    <div class="media-upload__preview-grid" id="image-preview" @style([! $item->thumbnailUrl() ? 'display:none' : null])>
                        @if ($item->thumbnailUrl())
                            <img src="{{ $item->thumbnailUrl() }}" alt="{{ $item->caption ?: $spot->name }}">
                        @endif
                    </div>
                </div>

                <label>
                    <span>画像URL / ストレージパス</span>
                    <input type="text" name="path" id="image-path-input" value="{{ old('path', $item->path) }}">
                    <small>{{ $item->exists ? 'アップロードの代わりにURLや既存ストレージパスを指定することもできます。' : '一括アップロードしない場合だけ、1件分のURLや既存ストレージパスを指定できます。' }}</small>
                </label>

                <label>
                    <span>サムネイルパス</span>
                    <input type="text" name="thumbnail_path" value="{{ old('thumbnail_path', $item->thumbnail_path) }}">
                </label>
            @else
                <label>
                    <span>YouTube埋め込みタグ</span>
                    <textarea name="path" rows="5" placeholder='<iframe src="https://www.youtube.com/embed/..."></iframe>'>{{ old('path', $item->path) }}</textarea>
                    <small>YouTubeの埋め込みタグをそのまま貼り付けてください。</small>
                </label>
            @endif

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
                <button type="submit" class="button-primary">{{ $item->exists ? '更新する' : ($mediaType === 'image' ? '画像を追加する' : '動画を追加する') }}</button>
                <a class="button-secondary" href="{{ route('admin.spots.media.index', ['spot' => $spot, 'type' => $mediaType]) }}">一覧へ戻る</a>
            </div>
        </form>
    </section>
@endsection
