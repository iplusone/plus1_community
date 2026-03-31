@extends('layouts.app')

@section('title', $member->exists ? 'スタッフ編集' : 'スタッフ追加')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>{{ $member->exists ? 'スタッフ編集' : 'スタッフ追加' }}</h1>
            </div>
        </div>

        <form method="POST" action="{{ $formAction }}" class="form-card">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <label>
                <span>名前</span>
                <input type="text" name="name" value="{{ old('name', $member->name) }}" required>
            </label>

            <label>
                <span>プロフィール</span>
                <textarea name="profile" rows="4">{{ old('profile', $member->profile) }}</textarea>
            </label>

            <label>
                <span>画像パス</span>
                <input type="text" name="image_path" value="{{ old('image_path', $member->image_path) }}">
            </label>

            <label>
                <span>表示順</span>
                <input type="number" name="sort_order" value="{{ old('sort_order', $member->sort_order ?? 0) }}" min="0">
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
                <button type="submit" class="button-primary">{{ $member->exists ? '更新する' : '追加する' }}</button>
                <a class="button-secondary" href="{{ route('admin.spots.staff.index', $spot) }}">一覧へ戻る</a>
            </div>
        </form>
    </section>
@endsection
