@php
    $href = $spot->source === 'mlit'
        ? route('mlit-spots.show', $spot->source_id)
        : route('spots.show', $spot->slug);
    $initial = preg_replace('/^[^\p{L}\p{N}]+/u', '', $spot->name);
    $initial = strtoupper(mb_substr($initial, 0, 1));
    if ($initial === '') { $initial = mb_substr($spot->name, 0, 1); }
@endphp
<article class="spot-card">
    <a href="{{ $href }}" class="spot-card__link" aria-label="{{ $spot->name }} の詳細を見る">
        <div class="spot-card__visual">
            <span>{{ $initial }}</span>
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
    </a>
</article>
