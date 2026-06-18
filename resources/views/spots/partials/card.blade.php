<article class="spot-card">
    @if ($spot->source === 'spot')
        <a href="{{ route('spots.show', $spot->slug) }}" class="spot-card__link" aria-label="{{ $spot->name }} の詳細を見る">
    @else
        <div class="spot-card__link">
    @endif
        <div class="spot-card__visual">
            <span>{{ strtoupper(mb_substr($spot->name, 0, 1)) }}</span>
        </div>
        <div class="spot-card__body">
            <p class="eyebrow">
                @if ($spot->source === 'mlit')
                    {{ $subCategoryLabels[$spot->sub_category] ?? $categoryLabels[$spot->category] ?? $spot->category }}
                @else
                    Spot
                @endif
            </p>
            <h3>{{ $spot->name }}</h3>
            @if ($spot->full_address)
                <p>{{ $spot->full_address }}</p>
            @endif
            @if ($spot->source === 'spot')
                <div class="mini-meta">
                    <span>{{ optional(\Carbon\Carbon::parse($spot->published_at))->format('Y.m.d') }}</span>
                    <span>PV {{ number_format($spot->view_count) }}</span>
                </div>
            @endif
        </div>
    @if ($spot->source === 'spot')
        </a>
    @else
        </div>
    @endif
</article>
