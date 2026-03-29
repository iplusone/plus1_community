<article class="spot-card">
    <div class="spot-card__visual">
        <span>{{ strtoupper(substr($spot->name, 0, 1)) }}</span>
    </div>
    <div class="spot-card__body">
        <p class="eyebrow">Spot</p>
        <h3>{{ $spot->name }}</h3>
        <p>{{ trim(collect([$spot->prefecture, $spot->city, $spot->town])->filter()->join(' ')) ?: '住所未設定' }}</p>
        <a href="{{ route('spots.show', $spot) }}">詳細を見る</a>
    </div>
</article>
