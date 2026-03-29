@extends('layouts.app')

@section('title', 'スポット検索')

@section('content')
    <section class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Search</p>
                <h1>スポット検索</h1>
            </div>
        </div>

        <form method="GET" action="{{ route('spots.index') }}" class="search-panel">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="キーワード">
            <input type="text" name="prefecture" value="{{ $filters['prefecture'] ?? '' }}" placeholder="都道府県">
            <input id="genre-input" type="text" name="genre" value="{{ $filters['genre'] ?? '' }}" placeholder="ジャンル" list="genre-suggestions">
            <datalist id="genre-suggestions"></datalist>
            <input id="tag-input" type="text" name="tag" value="{{ $filters['tag'] ?? '' }}" placeholder="タグ" list="tag-suggestions">
            <datalist id="tag-suggestions"></datalist>
            <select name="sort">
                <option value="latest" @selected($sort === 'latest')>新着順</option>
                <option value="popular" @selected($sort === 'popular')>人気順</option>
            </select>
            <select name="view">
                <option value="card" @selected($viewMode === 'card')>カード表示</option>
                <option value="list" @selected($viewMode === 'list')>リスト表示</option>
            </select>
            <button type="submit" class="button-primary">検索</button>
        </form>

        @if ($dbWarning)
            <div class="notice-panel compact">
                <p>{{ $dbWarning }}</p>
            </div>
        @endif
    </section>

    <section class="section-block">
        <div class="result-meta">
            <p>
                表示件数:
                @if ($spots instanceof \Illuminate\Contracts\Pagination\Paginator)
                    {{ $spots->total() }}
                @else
                    0
                @endif
                件
            </p>
        </div>

        <div class="{{ $viewMode === 'list' ? 'spot-list' : 'spot-grid' }}">
            @forelse ($spots as $spot)
                @include($viewMode === 'list' ? 'spots.partials.list-item' : 'spots.partials.card', ['spot' => $spot])
            @empty
                <div class="empty-panel">条件に合うスポットはありません。</div>
            @endforelse
        </div>

        @if ($spots instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="pagination-wrap">
                {{ $spots->links() }}
            </div>
        @endif
    </section>
@endsection
