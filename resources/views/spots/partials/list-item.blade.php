@php
    $href = $spot->source === 'mlit'
        ? route('mlit-spots.show', $spot->source_id)
        : route('spots.show', $spot->slug);
    $initial = preg_replace('/^[^\p{L}\p{N}]+/u', '', $spot->name);
    $initial = strtoupper(mb_substr($initial, 0, 1));
    if ($initial === '') { $initial = mb_substr($spot->name, 0, 1); }
@endphp
<article class="list-item">
    <div class="list-item__visual">
        <span>{{ $initial }}</span>
    </div>
    <div class="list-item__body">
        <div>
            <p class="eyebrow">
                @if ($spot->source === 'mlit')
                    {{ $subCategoryLabels[$spot->sub_category] ?? $categoryLabels[$spot->category] ?? $spot->category }}
                @else
                    Spot
                @endif
            </p>
            <h3><a href="{{ $href }}">{{ $spot->name }}</a></h3>
            @if ($spot->full_address)
                <p>{{ $spot->full_address }}</p>
            @endif
        </div>
        <div class="list-item__meta">
            @if ($spot->source === 'spot')
                <span>公開: {{ optional(\Carbon\Carbon::parse($spot->published_at))->format('Y-m-d') }}</span>
                <span>PV: {{ number_format($spot->view_count) }}</span>
            @else
                <span>{{ $categoryLabels[$spot->category] ?? '' }}</span>
            @endif
            <a href="{{ $href }}">詳細を見る</a>
        </div>
    </div>
</article>
