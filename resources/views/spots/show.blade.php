@extends('layouts.app')

@section('title', $spot->name)

@section('content')
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

    <section class="hero-panel detail-hero">
        <div>
            <p class="eyebrow">Base Detail</p>
            <h1>{{ $spot->name }}</h1>
            <p class="hero-copy">{{ $spot->description ?: 'このスポットの紹介文はまだ登録されていません。' }}</p>
            <div class="chip-row">
                <span>{{ trim(collect([$spot->prefecture, $spot->city, $spot->town])->filter()->join(' ')) ?: '地域未設定' }}</span>
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
                <strong>{{ $spot->children->count() }}</strong>
            </div>
        </div>
    </section>

    <section class="detail-grid detail-grid--primary">
        <article class="content-card content-card--wide">
            <h2>基本情報</h2>
            <div class="info-grid">
                <div>
                    <span>拠点名</span>
                    <strong>{{ $spot->name }}</strong>
                </div>
                <div>
                    <span>住所</span>
                    <strong>{{ trim(collect([$spot->prefecture, $spot->city, $spot->town, $spot->address_line])->filter()->join(' ')) ?: '未設定' }}</strong>
                </div>
                <div>
                    <span>電話番号</span>
                    <strong>{{ $spot->phone ?: '未設定' }}</strong>
                </div>
                <div>
                    <span>営業時間</span>
                    <strong>{{ $spot->business_hours_text ?: '未設定' }}</strong>
                </div>
                <div>
                    <span>定休日</span>
                    <strong>{{ $spot->holiday_text ?: '未設定' }}</strong>
                </div>
                <div>
                    <span>スラッグ</span>
                    <strong>{{ $spot->slug }}</strong>
                </div>
            </div>
        </article>

        <article class="content-card">
            <h2>営業時間詳細</h2>
            @forelse ($spot->businessHours->sortBy('day_of_week') as $hours)
                <div class="stack-item stack-item--compact">
                    <strong>{{ ['日', '月', '火', '水', '木', '金', '土'][$hours->day_of_week] }}曜</strong>
                    <p>
                        @if ($hours->is_closed)
                            休業
                        @else
                            {{ optional($hours->opens_at)->format('H:i') }} - {{ optional($hours->closes_at)->format('H:i') }}
                        @endif
                        @if ($hours->note)
                            / {{ $hours->note }}
                        @endif
                    </p>
                </div>
            @empty
                <p>営業時間詳細はまだ登録されていません。</p>
            @endforelse
        </article>
    </section>

    <section class="detail-grid">
        <article class="content-card">
            <h2>特徴</h2>
            <p>{{ $spot->features ?: '特徴はまだ登録されていません。' }}</p>
        </article>

        <article class="content-card">
            <h2>アクセス</h2>
            <p>{{ $spot->access_text ?: 'アクセス情報はまだ登録されていません。' }}</p>
            @php
                $mapQuery = trim(collect([$spot->prefecture, $spot->city, $spot->town, $spot->address_line])->filter()->join(' '));
            @endphp
            @if ($mapQuery !== '')
                <div class="hero-actions">
                    <a class="button-secondary" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($mapQuery) }}" target="_blank" rel="noreferrer">地図で見る</a>
                </div>
            @endif
        </article>

        <article class="content-card">
            <h2>サービス</h2>
            @forelse ($spot->services as $service)
                <div class="stack-item">
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
                <p>サービス情報はまだ登録されていません。</p>
            @endforelse
        </article>

        <article class="content-card">
            <h2>メディア</h2>
            @forelse ($spot->media as $media)
                <div class="media-card">
                    <div class="media-card__visual">{{ strtoupper($media->type) }}</div>
                    <div>
                        <strong>{{ $media->caption ?: 'キャプション未設定' }}</strong>
                        <p>{{ $media->path }}</p>
                    </div>
                </div>
            @empty
                <p>メディアはまだ登録されていません。</p>
            @endforelse
        </article>

        <article class="content-card">
            <h2>スタッフ</h2>
            @forelse ($spot->staff as $member)
                <div class="profile-card">
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
                <div class="coupon-card">
                    <strong>{{ $coupon->title }}</strong>
                    <p>{{ $coupon->content }}</p>
                    <small>
                        条件: {{ $coupon->conditions ?: '条件未設定' }}
                        @if ($coupon->expires_at)
                            / 有効期限: {{ $coupon->expires_at->format('Y-m-d') }}
                        @endif
                    </small>
                </div>
            @empty
                <p>クーポンはまだありません。</p>
            @endforelse
        </article>

        <article class="content-card">
            <h2>外部コンテンツ連携</h2>
            @if ($spot->wordpressSite)
                <div class="stack-item">
                    <strong>WordPress 連携あり</strong>
                    <p>{{ $spot->wordpressSite->base_url }}</p>
                    <p>最終同期: {{ optional($spot->wordpressSite->last_synced_at)->format('Y-m-d H:i') ?: '未同期' }}</p>
                </div>
            @else
                <p>WordPress 連携はまだ設定されていません。</p>
            @endif
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

    <section class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Related</p>
                <h2>関連する拠点</h2>
            </div>
            <a href="{{ route('spots.index') }}">一覧へ</a>
        </div>
        <div class="spot-grid">
            @forelse ($relatedSpots as $relatedSpot)
                @include('spots.partials.card', ['spot' => $relatedSpot])
            @empty
                <div class="empty-panel">関連拠点はまだありません。</div>
            @endforelse
        </div>
    </section>
@endsection
