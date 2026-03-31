@extends('layouts.app')

@section('title', $menu->exists ? 'メニュー編集' : 'メニュー追加')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <nav class="breadcrumbs" style="margin-top:.5rem">
            <a href="{{ route('admin.spots.services.index', $spot) }}">サービス一覧</a>
            <span>/</span>
            <a href="{{ route('admin.spots.services.menus.index', [$spot, $service]) }}">{{ $service->title }}</a>
            <span>/</span>
            <span>{{ $menu->exists ? 'メニュー編集' : 'メニュー追加' }}</span>
        </nav>

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>{{ $menu->exists ? 'メニュー編集' : 'メニュー追加' }}</h1>
            </div>
        </div>

        <form method="POST" action="{{ $formAction }}" class="form-card">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <label>
                <span>名前</span>
                <input type="text" name="name" value="{{ old('name', $menu->name) }}" required>
            </label>

            <label>
                <span>説明</span>
                <textarea name="description" rows="4">{{ old('description', $menu->description) }}</textarea>
            </label>

            <label>
                <span>価格</span>
                <input type="text" name="price_text" value="{{ old('price_text', $menu->price_text) }}"
                       placeholder="例: ¥3,000〜">
            </label>

            <label>
                <span>表示順</span>
                <input type="number" name="sort_order" value="{{ old('sort_order', $menu->sort_order ?? 0) }}" min="0">
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
                <button type="submit" class="button-primary">{{ $menu->exists ? '更新する' : '追加する' }}</button>
                <a class="button-secondary"
                   href="{{ route('admin.spots.services.menus.index', [$spot, $service]) }}">一覧へ戻る</a>
            </div>
        </form>
    </section>
@endsection
