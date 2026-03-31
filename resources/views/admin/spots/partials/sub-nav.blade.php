<nav class="breadcrumbs">
    <a href="{{ route('admin.spots.index') }}">スポット管理</a>
    <span>/</span>
    <span>{{ $spot->name }}</span>
</nav>

<div class="admin-tabs">
    <a href="{{ route('admin.spots.edit', $spot) }}"
       @class(['is-active' => request()->routeIs('admin.spots.edit')])>基本情報</a>
    <a href="{{ route('admin.spots.staff.index', $spot) }}"
       @class(['is-active' => request()->routeIs('admin.spots.staff.*')])>スタッフ</a>
    <a href="{{ route('admin.spots.coupons.index', $spot) }}"
       @class(['is-active' => request()->routeIs('admin.spots.coupons.*')])>クーポン</a>
    <a href="{{ route('admin.spots.media.index', $spot) }}"
       @class(['is-active' => request()->routeIs('admin.spots.media.*')])>メディア</a>
    <a href="{{ route('admin.spots.services.index', $spot) }}"
       @class(['is-active' => request()->routeIs('admin.spots.services.*')])>サービス</a>
    <a href="{{ route('admin.spots.stations.index', $spot) }}"
       @class(['is-active' => request()->routeIs('admin.spots.stations.*')])>最寄り駅</a>
</div>
