@extends('layouts.app')

@section('title', $service->exists ? 'サービス編集' : 'サービス追加')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>{{ $service->exists ? 'サービス編集' : 'サービス追加' }}</h1>
            </div>
        </div>

        <form method="POST" action="{{ $formAction }}" class="form-card">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <label>
                <span>タイトル</span>
                <input type="text" name="title" value="{{ old('title', $service->title) }}" required>
            </label>

            <label>
                <span>説明</span>
                <textarea name="description" rows="4">{{ old('description', $service->description) }}</textarea>
            </label>

            <label>
                <span>表示順</span>
                <input type="number" name="sort_order" value="{{ old('sort_order', $service->sort_order ?? 0) }}" min="0">
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
                <button type="submit" class="button-primary">{{ $service->exists ? '更新する' : '追加する' }}</button>
                <a class="button-secondary" href="{{ route('admin.spots.services.index', $spot) }}">一覧へ戻る</a>
            </div>
        </form>
    </section>
@endsection
