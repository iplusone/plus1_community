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

        <form method="POST" action="{{ $formAction }}" class="form-card">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <label>
                <span>種別</span>
                <select name="type">
                    <option value="image" @selected(old('type', $item->type) === 'image')>画像</option>
                    <option value="video" @selected(old('type', $item->type) === 'video')>動画</option>
                </select>
            </label>

            <label>
                <span>パス（URL またはストレージパス）</span>
                <input type="text" name="path" value="{{ old('path', $item->path) }}" required>
            </label>

            <label>
                <span>サムネイルパス</span>
                <input type="text" name="thumbnail_path" value="{{ old('thumbnail_path', $item->thumbnail_path) }}">
            </label>

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
