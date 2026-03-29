<article class="list-item">
    <div class="list-item__visual">
        <span>{{ strtoupper(substr($spot->name, 0, 1)) }}</span>
    </div>
    <div class="list-item__body">
        <div>
            <p class="eyebrow">Spot</p>
            <h3>{{ $spot->name }}</h3>
            <p>{{ trim(collect([$spot->prefecture, $spot->city, $spot->town, $spot->address_line])->filter()->join(' ')) ?: '住所未設定' }}</p>
        </div>
        <div class="list-item__meta">
            <span>公開: {{ optional($spot->published_at)->format('Y-m-d') ?: '未設定' }}</span>
            <span>PV: {{ number_format($spot->view_count) }}</span>
            <a href="{{ route('spots.show', $spot) }}">詳細を見る</a>
        </div>
    </div>
</article>
