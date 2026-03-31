<article class="spot-card">
    <div class="spot-card__visual">
        <span>{{ strtoupper(mb_substr($spot->name, 0, 1)) }}</span>
    </div>
    <div class="spot-card__body">
        <p class="eyebrow">Spot</p>
        <h3>{{ $spot->name }}</h3>
        <p>{{ trim(collect([$spot->prefecture, $spot->city, $spot->town])->filter()->join(' ')) ?: '住所未設定' }}</p>
        @if ($spot->description)
            <p class="spot-card__excerpt">{{ \Illuminate\Support\Str::limit($spot->description, 72) }}</p>
        @endif
        <div class="mini-meta">
            <span>{{ optional($spot->published_at)->format('Y.m.d') ?: '公開日未設定' }}</span>
            <span>PV {{ number_format($spot->view_count) }}</span>
            <span>配下 {{ $spot->children_count ?? 0 }}</span>
        </div>
        @if ($spot->genres->isNotEmpty())
            <div class="tag-row">
                @foreach ($spot->genres->take(2) as $genre)
                    <span>{{ $genre->name }}</span>
                @endforeach
            </div>
        @endif
        <a href="{{ route('spots.show', $spot) }}">詳細を見る</a>
    </div>
</article>
