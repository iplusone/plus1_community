<article class="spot-card">
    <a href="{{ route('spots.show', $spot) }}" class="spot-card__link" aria-label="{{ $spot->name }} の詳細を見る">
        <div class="spot-card__visual">
            @if ($spot->cardImageUrl())
                <img src="{{ $spot->cardImageUrl() }}" alt="{{ $spot->name }}" loading="lazy">
            @else
                <span>{{ strtoupper(mb_substr($spot->name, 0, 1)) }}</span>
            @endif
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
            <span class="spot-card__cta" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" role="presentation">
                    <path d="M7 17L17 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 7H17V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </div>
    </a>
</article>
