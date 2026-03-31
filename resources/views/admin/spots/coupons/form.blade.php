@extends('layouts.app')

@section('title', $coupon->exists ? 'クーポン編集' : 'クーポン追加')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>{{ $coupon->exists ? 'クーポン編集' : 'クーポン追加' }}</h1>
            </div>
        </div>

        <form method="POST" action="{{ $formAction }}" class="form-card">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <label>
                <span>タイトル</span>
                <input type="text" name="title" value="{{ old('title', $coupon->title) }}" required>
            </label>

            <label>
                <span>内容</span>
                <textarea name="content" rows="4">{{ old('content', $coupon->content) }}</textarea>
            </label>

            <label>
                <span>利用条件</span>
                <textarea name="conditions" rows="3">{{ old('conditions', $coupon->conditions) }}</textarea>
            </label>

            <div class="form-grid">
                <label>
                    <span>開始日時</span>
                    <input type="datetime-local" name="starts_at"
                           value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d\TH:i')) }}">
                </label>
                <label>
                    <span>終了日時</span>
                    <input type="datetime-local" name="expires_at"
                           value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d\TH:i')) }}">
                </label>
            </div>

            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $coupon->is_active ?? true))>
                <span>有効にする</span>
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
                <button type="submit" class="button-primary">{{ $coupon->exists ? '更新する' : '追加する' }}</button>
                <a class="button-secondary" href="{{ route('admin.spots.coupons.index', $spot) }}">一覧へ戻る</a>
            </div>
        </form>
    </section>
@endsection
