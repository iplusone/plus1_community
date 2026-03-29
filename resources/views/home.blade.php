@extends('layouts.app')

@section('title', 'トップ')

@section('content')
    <section class="hero-panel">
        <div>
            <p class="eyebrow">Portal Platform</p>
            <h1>拠点情報を見つけやすく、伝わりやすくするポータル基盤</h1>
            <p class="hero-copy">
                組織階層を保ちながら、各拠点(スポット)が独立したページを持ち、基本情報、サービス、スタッフ、クーポンまで一画面で届けるための土台です。
            </p>
            <div class="hero-actions">
                <a class="button-primary" href="{{ route('spots.index') }}">拠点を探す</a>
                <a class="button-secondary" href="{{ route('admin.spots.index') }}">管理画面へ</a>
            </div>
        </div>

        <div class="stats-panel">
            <div>
                <span>総拠点数</span>
                <strong>{{ number_format($stats['total_spots']) }}</strong>
            </div>
            <div>
                <span>公開中</span>
                <strong>{{ number_format($stats['public_spots']) }}</strong>
            </div>
        </div>
    </section>

    @if ($dbWarning)
        <section class="notice-panel">
            <strong>セットアップ待ち</strong>
            <p>{{ $dbWarning }}</p>
        </section>
    @endif

    <section class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Featured</p>
                <h2>おすすめ拠点</h2>
            </div>
            <a href="{{ route('spots.index') }}">もっと見る</a>
        </div>
        <div class="spot-grid">
            @forelse ($featuredSpots as $spot)
                @include('spots.partials.card', ['spot' => $spot])
            @empty
                <div class="empty-panel">おすすめに表示する拠点はまだありません。</div>
            @endforelse
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Latest</p>
                <h2>最新公開拠点</h2>
            </div>
            <a href="{{ route('spots.index', ['sort' => 'latest']) }}">もっと見る</a>
        </div>
        <div class="spot-grid">
            @forelse ($latestSpots as $spot)
                @include('spots.partials.card', ['spot' => $spot])
            @empty
                <div class="empty-panel">最新公開拠点はまだありません。</div>
            @endforelse
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Random</p>
                <h2>ランダムピック</h2>
            </div>
            <a href="{{ route('spots.index') }}">もっと見る</a>
        </div>
        <div class="spot-grid">
            @forelse ($randomSpots as $spot)
                @include('spots.partials.card', ['spot' => $spot])
            @empty
                <div class="empty-panel">ランダム表示できる拠点はまだありません。</div>
            @endforelse
        </div>
    </section>
@endsection
