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
            <input type="text" name="genre" value="{{ $filters['genre'] ?? '' }}" placeholder="ジャンル">
            <input type="text" name="tag" value="{{ $filters['tag'] ?? '' }}" placeholder="タグ">
            <button type="submit" class="button-primary">検索</button>
        </form>

        @if ($dbWarning)
            <div class="notice-panel compact">
                <p>{{ $dbWarning }}</p>
            </div>
        @endif
    </section>

    <section class="section-block">
        <div class="spot-grid">
            @forelse ($spots as $spot)
                @include('spots.partials.card', ['spot' => $spot])
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
