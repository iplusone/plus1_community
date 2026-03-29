@extends('layouts.app')

@section('title', $spot->name)

@section('content')
    <section class="hero-panel detail-hero">
        <div>
            <p class="eyebrow">Spot Detail</p>
            <h1>{{ $spot->name }}</h1>
            <p class="hero-copy">{{ $spot->description ?: 'このスポットの紹介文はまだ登録されていません。' }}</p>
            <div class="chip-row">
                <span>{{ trim(collect([$spot->prefecture, $spot->city, $spot->town])->filter()->join(' ')) ?: '地域未設定' }}</span>
                <span>{{ $spot->phone ?: '電話番号未設定' }}</span>
                <span>{{ $spot->business_hours_text ?: '営業時間未設定' }}</span>
            </div>
        </div>
        <div class="stats-panel">
            <div>
                <span>公開日時</span>
                <strong>{{ optional($spot->published_at)->format('Y-m-d H:i') ?: '未公開' }}</strong>
            </div>
            <div>
                <span>閲覧数</span>
                <strong>{{ number_format($spot->view_count) }}</strong>
            </div>
        </div>
    </section>

    <section class="detail-grid">
        <article class="content-card">
            <h2>特徴</h2>
            <p>{{ $spot->features ?: '特徴はまだ登録されていません。' }}</p>
        </article>

        <article class="content-card">
            <h2>アクセス</h2>
            <p>{{ $spot->access_text ?: 'アクセス情報はまだ登録されていません。' }}</p>
        </article>

        <article class="content-card">
            <h2>サービス</h2>
            @forelse ($spot->services as $service)
                <div class="stack-item">
                    <strong>{{ $service->title }}</strong>
                    <p>{{ $service->description }}</p>
                </div>
            @empty
                <p>サービス情報はまだ登録されていません。</p>
            @endforelse
        </article>

        <article class="content-card">
            <h2>スタッフ</h2>
            @forelse ($spot->staff as $member)
                <div class="stack-item">
                    <strong>{{ $member->name }}</strong>
                    <p>{{ $member->profile }}</p>
                </div>
            @empty
                <p>スタッフ情報はまだ登録されていません。</p>
            @endforelse
        </article>

        <article class="content-card">
            <h2>クーポン</h2>
            @forelse ($spot->coupons as $coupon)
                <div class="stack-item">
                    <strong>{{ $coupon->title }}</strong>
                    <p>{{ $coupon->content }}</p>
                </div>
            @empty
                <p>クーポンはまだありません。</p>
            @endforelse
        </article>

        <article class="content-card">
            <h2>配下スポット</h2>
            @forelse ($spot->children as $child)
                <div class="stack-item">
                    <a href="{{ route('spots.show', $child) }}">{{ $child->name }}</a>
                </div>
            @empty
                <p>配下スポットはありません。</p>
            @endforelse
        </article>
    </section>
@endsection
