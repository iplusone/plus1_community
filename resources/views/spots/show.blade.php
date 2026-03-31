@extends('layouts.app')

@section('title', $spot->name)

@section('content')
    @php
        $fullAddress = trim(collect([$spot->prefecture, $spot->city, $spot->town, $spot->address_line])->filter()->join(' '));
        $mapQuery = $fullAddress;
        $weekdayLabels = ['日', '月', '火', '水', '木', '金', '土'];
    @endphp

    <section class="breadcrumbs">
        <a href="{{ route('home') }}">トップ</a>
        <span>/</span>
        <a href="{{ route('spots.index') }}">拠点一覧</a>
        @if ($spot->parent)
            <span>/</span>
            <a href="{{ route('spots.show', $spot->parent) }}">{{ $spot->parent->name }}</a>
        @endif
        <span>/</span>
        <strong>{{ $spot->name }}</strong>
    </section>

    <section class="spot-header">
        <div>
            <p class="eyebrow">Base Detail</p>
            <h1>{{ $spot->name }}</h1>
            <p class="hero-copy">{{ $spot->description ?: 'この拠点(スポット)の紹介文はまだ登録されていません。' }}</p>
            <div class="chip-row">
                <span>{{ $fullAddress ?: '住所未設定' }}</span>
                <span>{{ $spot->phone ?: '電話番号未設定' }}</span>
                <span>{{ $spot->business_hours_text ?: '営業時間未設定' }}</span>
                <span>{{ $spot->holiday_text ?: '定休日未設定' }}</span>
            </div>
            @if ($spot->genres->isNotEmpty() || $spot->tags->isNotEmpty())
                <div class="tag-row spacious">
                    @foreach ($spot->genres as $genre)
                        <span>{{ $genre->name }}</span>
                    @endforeach
                    @foreach ($spot->tags as $tag)
                        <span>#{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif
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
            <div>
                <span>配下拠点</span>
                <strong>{{ $spot->children_count }}</strong>
            </div>
        </div>
    </section>

    <nav class="detail-tabs">
        <a href="#spot-info">拠点情報</a>
        <a href="#news-blog" class="is-muted">ニュース&ブログ</a>
        <a href="#photos">写真</a>
        <a href="#services">サービス</a>
        <a href="#staff">スタッフ紹介</a>
        <a href="#access">地図/アクセス</a>
    </nav>

    <section class="spot-detail-layout">
        <div class="spot-main">
            <article id="spot-info" class="content-card section-anchor">
                <h2>{{ $spot->name }} の拠点情報</h2>
                <div class="info-table">
                    <div class="info-table__row">
                        <span>住所</span>
                        <strong>{{ $fullAddress ?: '未設定' }}</strong>
                    </div>
                    <div class="info-table__row">
                        <span>電話</span>
                        <strong>{{ $spot->phone ?: '未設定' }}</strong>
                    </div>
                    <div class="info-table__row">
                        <span>営業時間</span>
                        <strong>{{ $spot->business_hours_text ?: '未設定' }}</strong>
                    </div>
                    <div class="info-table__row">
                        <span>定休日</span>
                        <strong>{{ $spot->holiday_text ?: '未設定' }}</strong>
                    </div>
                    <div class="info-table__row">
                        <span>特徴</span>
                        <strong>{{ $spot->features ?: '未設定' }}</strong>
                    </div>
                    <div class="info-table__row">
                        <span>スラッグ</span>
                        <strong>{{ $spot->slug }}</strong>
                    </div>
                </div>
            </article>

            <article id="access" class="content-card section-anchor">
                <h2>{{ $spot->name }} の地図</h2>
                @if ($mapQuery !== '')
                    <div class="map-frame">
                        <iframe
                            src="https://www.google.com/maps?q={{ urlencode($mapQuery) }}&output=embed"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="{{ $spot->name }} の地図"
                        ></iframe>
                    </div>
                    <div class="info-table compact">
                        <div class="info-table__row">
                            <span>住所</span>
                            <strong>{{ $fullAddress }}</strong>
                        </div>
                        <div class="info-table__row">
                            <span>アクセス</span>
                            <strong>{{ $spot->access_text ?: 'アクセス説明は未設定です。' }}</strong>
                        </div>
                        @if ($spot->spotStations->isNotEmpty())
                            <div class="info-table__row">
                                <span>最寄り駅</span>
                                <div class="station-list">
                                    @foreach ($spot->spotStations->sortBy('sort_order') as $spotStation)
                                        @php $station = $spotStation->station; @endphp
                                        <div class="station-list__item">
                                            <a href="{{ route('spots.index', ['area' => '[駅] ' . $station->station_name]) }}">{{ $station->station_name }}駅</a>
                                            @if ($station->railwayRoutes->isNotEmpty())
                                                <span class="station-list__routes">
                                                    @foreach ($station->railwayRoutes as $route)
                                                        <a href="{{ route('spots.index', ['area' => '[路線] ' . $route->line_name]) }}">{{ $route->line_name }}</a>
                                                    @endforeach
                                                </span>
                                            @endif
                                            <span class="station-list__walk">徒歩 {{ $spotStation->walking_minutes }} 分</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="hero-actions">
                        <a class="button-secondary" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($mapQuery) }}" target="_blank" rel="noreferrer">Googleマップで開く</a>
                    </div>
                @else
                    <p>地図情報はまだ登録されていません。</p>
                @endif
            </article>

            <article class="content-card">
                <h2>{{ $spot->name }} の営業時間</h2>
                <div class="hours-grid">
                    @forelse ($spot->businessHours->sortBy('day_of_week') as $hours)
                        <div class="hours-card">
                            <span>{{ $weekdayLabels[$hours->day_of_week] }}曜</span>
                            <strong>
                                @if ($hours->is_closed)
                                    休業
                                @else
                                    {{ optional($hours->opens_at)->format('H:i') }}〜{{ optional($hours->closes_at)->format('H:i') }}
                                @endif
                            </strong>
                            @if ($hours->note)
                                <small>{{ $hours->note }}</small>
                            @endif
                        </div>
                    @empty
                        <p>営業時間詳細はまだ登録されていません。</p>
                    @endforelse
                </div>
            </article>

            <article id="news-blog" class="content-card section-anchor">
                <h2>ニュース&ブログ</h2>
                <div class="empty-panel compact">この拠点(スポット)のニュース&ブログは準備中です。</div>
            </article>

            <article id="photos" class="content-card section-anchor">
                <h2>写真</h2>
                <div class="photo-grid">
                    @forelse ($spot->media as $media)
                        <div class="photo-tile">
                            <div class="photo-tile__visual">{{ strtoupper($media->type) }}</div>
                            <strong>{{ $media->caption ?: 'キャプション未設定' }}</strong>
                            <p>{{ $media->path }}</p>
                        </div>
                    @empty
                        <div class="empty-panel compact">写真はまだ登録されていません。</div>
                    @endforelse
                </div>
            </article>

            <article id="services" class="content-card section-anchor">
                <h2>サービス</h2>
                <div class="service-stack">
                    @forelse ($spot->services as $service)
                        <div class="service-card">
                            <strong>{{ $service->title }}</strong>
                            <p>{{ $service->description }}</p>
                            @if ($service->menus->isNotEmpty())
                                <div class="sub-list">
                                    @foreach ($service->menus as $menu)
                                        <div class="sub-list__item">
                                            <div>
                                                <strong>{{ $menu->name }}</strong>
                                                <p>{{ $menu->description }}</p>
                                            </div>
                                            <span>{{ $menu->price_text ?: '料金未設定' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="empty-panel compact">サービス情報はまだ登録されていません。</div>
                    @endforelse
                </div>
            </article>

            <article id="staff" class="content-card section-anchor">
                <h2>スタッフ紹介</h2>
                <div class="staff-grid">
                    @forelse ($spot->staff as $member)
                        <div class="profile-card">
                            <div class="profile-card__avatar">{{ strtoupper(mb_substr($member->name, 0, 1)) }}</div>
                            <div>
                                <strong>{{ $member->name }}</strong>
                                <p>{{ $member->profile }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-panel compact">スタッフ情報はまだ登録されていません。</div>
                    @endforelse
                </div>
            </article>
        </div>

        <aside class="spot-sidebar">
            <section class="sidebar-card">
                <h2>データ</h2>
                <div class="sidebar-list">
                    <div>
                        <span>電話番号</span>
                        <strong>{{ $spot->phone ?: '未設定' }}</strong>
                    </div>
                    <div>
                        <span>拠点名</span>
                        <strong>{{ $spot->name }}</strong>
                    </div>
                    <div>
                        <span>住所</span>
                        <strong>{{ $fullAddress ?: '未設定' }}</strong>
                    </div>
                    <div>
                        <span>URL</span>
                        <strong>
                            @if ($spot->wordpressSite)
                                <a href="{{ $spot->wordpressSite->base_url }}" target="_blank" rel="noreferrer">拠点HP</a>
                            @else
                                未設定
                            @endif
                        </strong>
                    </div>
                </div>
            </section>

            <section class="sidebar-card">
                <h2>ジャンル / タグ</h2>
                @if ($spot->genres->isNotEmpty() || $spot->tags->isNotEmpty())
                    <div class="tag-row">
                        @foreach ($spot->genres as $genre)
                            <span>{{ $genre->name }}</span>
                        @endforeach
                        @foreach ($spot->tags as $tag)
                            <span>#{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @else
                    <p>未設定</p>
                @endif
            </section>

            <section class="sidebar-card">
                <h2>配下拠点</h2>
                @forelse ($spot->children as $child)
                    <div class="sidebar-link">
                        <a href="{{ route('spots.show', $child) }}">{{ $child->name }}</a>
                    </div>
                @empty
                    <p>配下拠点はありません。</p>
                @endforelse
            </section>

            @if ($spot->spotStations->isNotEmpty())
            <section class="sidebar-card">
                <h2>最寄り駅</h2>
                <div class="station-list">
                    @foreach ($spot->spotStations->sortBy('sort_order') as $spotStation)
                        @php $station = $spotStation->station; @endphp
                        <div class="station-list__item">
                            <a href="{{ route('spots.index', ['area' => '[駅] ' . $station->station_name]) }}">{{ $station->station_name }}駅</a>
                            @if ($station->railwayRoutes->isNotEmpty())
                                <span class="station-list__routes">
                                    @foreach ($station->railwayRoutes as $route)
                                        <a href="{{ route('spots.index', ['area' => '[路線] ' . $route->line_name]) }}">{{ $route->line_name }}</a>
                                    @endforeach
                                </span>
                            @endif
                            <span class="station-list__walk">徒歩 {{ $spotStation->walking_minutes }} 分</span>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

            <section class="sidebar-card">
                <h2>関連する拠点</h2>
                @forelse ($relatedSpots as $relatedSpot)
                    <div class="sidebar-link">
                        <a href="{{ route('spots.show', $relatedSpot) }}">{{ $relatedSpot->name }}</a>
                        <small>{{ trim(collect([$relatedSpot->prefecture, $relatedSpot->city])->filter()->join(' ')) }}</small>
                    </div>
                @empty
                    <p>関連拠点はまだありません。</p>
                @endforelse
            </section>
        </aside>
    </section>
@endsection
