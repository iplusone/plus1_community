@extends('layouts.app')

@section('title', $spot->name)

@section('content')
    <section class="breadcrumbs">
        <a href="{{ route('home') }}">トップ</a>
        <span>/</span>
        <a href="{{ route('spots.index') }}">スポット検索</a>
        <span>/</span>
        <a href="{{ route('spots.index', ['category' => $spot->category]) }}">{{ $categoryLabel }}</a>
        <span>/</span>
        <strong>{{ $spot->name }}</strong>
    </section>

    <section class="spot-header">
        <div>
            <p class="eyebrow">{{ $subCategoryLabel }}</p>
            <h1>{{ $spot->name }}</h1>
            <div class="chip-row">
                <span>{{ $spot->address ?: '住所未登録' }}</span>
            </div>
            <div class="tag-row spacious">
                <span>{{ $categoryLabel }}</span>
                @if ($subCategoryLabel !== $categoryLabel)
                    <span>{{ $subCategoryLabel }}</span>
                @endif
            </div>
        </div>

        <div class="stats-panel">
            <div>
                <span>データソース</span>
                <strong>国土数値情報</strong>
            </div>
            <div>
                <span>データセット</span>
                <strong>{{ $spot->dataset_code }}（{{ optional($spot->dataset)->name ?? $spot->dataset_code }}）</strong>
            </div>
            <div>
                <span>年度</span>
                <strong>{{ $spot->source_year ? $spot->source_year . '年' : '不明' }}</strong>
            </div>
        </div>
    </section>

    <section class="spot-detail-layout">
        <div class="spot-main">
            <article class="content-card">
                <h2>基本情報</h2>
                <div class="info-table">
                    <div class="info-table__row">
                        <span>施設名</span>
                        <strong>{{ $spot->name }}</strong>
                    </div>
                    <div class="info-table__row">
                        <span>カテゴリ</span>
                        <strong>{{ $categoryLabel }}（{{ $subCategoryLabel }}）</strong>
                    </div>
                    <div class="info-table__row">
                        <span>住所</span>
                        <strong>{{ $spot->address ?: '未登録' }}</strong>
                    </div>
                    @if ($spot->latitude && $spot->longitude)
                        <div class="info-table__row">
                            <span>緯度 / 経度</span>
                            <strong>{{ $spot->latitude }} / {{ $spot->longitude }}</strong>
                        </div>
                    @endif
                    @if ($spot->admin_area_code)
                        <div class="info-table__row">
                            <span>行政区域コード</span>
                            <strong>{{ $spot->admin_area_code }}</strong>
                        </div>
                    @endif
                </div>
            </article>

            @if (! empty($attrs))
                <article class="content-card">
                    <h2>詳細属性</h2>
                    <div class="info-table">
                        @foreach ($attrs as $key => $val)
                            <div class="info-table__row">
                                <span>{{ \App\Http\Controllers\MlitSpotController::attrLabel($key) }}</span>
                                <strong>{{ $val }}</strong>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endif

            <article class="content-card" id="access">
                <h2>地図</h2>
                @if ($mapQuery)
                    <div class="map-frame">
                        <iframe
                            src="https://www.google.com/maps?q={{ urlencode($mapQuery) }}&output=embed"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="{{ $spot->name }} の地図"
                        ></iframe>
                    </div>
                    <div class="hero-actions">
                        <a class="button-secondary"
                           href="https://www.google.com/maps/search/?api=1&query={{ urlencode($spot->address ?? $mapQuery) }}"
                           target="_blank" rel="noreferrer">Googleマップで開く</a>
                    </div>
                @else
                    <p>地図情報はまだ登録されていません。</p>
                @endif
            </article>
        </div>

        <aside class="spot-sidebar">
            <section class="sidebar-card">
                <h2>施設情報</h2>
                <div class="sidebar-list">
                    <div>
                        <span>カテゴリ</span>
                        <strong>{{ $categoryLabel }}</strong>
                    </div>
                    <div>
                        <span>種別</span>
                        <strong>{{ $subCategoryLabel }}</strong>
                    </div>
                    <div>
                        <span>住所</span>
                        <strong>{{ $spot->address ?: '未登録' }}</strong>
                    </div>
                    <div>
                        <span>データセット</span>
                        <strong>{{ $spot->dataset_code }}</strong>
                    </div>
                </div>
            </section>

            <section class="sidebar-card">
                <h2>同じカテゴリを検索</h2>
                <div class="sidebar-link">
                    <a href="{{ route('spots.index', ['category' => $spot->category]) }}">{{ $categoryLabel }}一覧</a>
                </div>
                <div class="sidebar-link">
                    <a href="{{ route('spots.index', ['category' => $spot->sub_category]) }}">{{ $subCategoryLabel }}一覧</a>
                </div>
                @if ($spot->address)
                    <div class="sidebar-link">
                        @php
                            preg_match('/^(.{2,3}[都道府県])/', $spot->address, $prefMatch);
                            $prefName = $prefMatch[1] ?? '';
                        @endphp
                        @if ($prefName)
                            <a href="{{ route('spots.index', ['q' => $prefName, 'category' => $spot->category]) }}">{{ $prefName }}の{{ $categoryLabel }}</a>
                        @endif
                    </div>
                @endif
            </section>
        </aside>
    </section>
@endsection
