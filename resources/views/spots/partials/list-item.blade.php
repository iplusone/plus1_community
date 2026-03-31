<article class="list-item">
    <div class="list-item__visual">
        <span>{{ strtoupper(mb_substr($spot->name, 0, 1)) }}</span>
    </div>
    <div class="list-item__body">
        <div>
            <p class="eyebrow">Spot</p>
            <h3>{{ $spot->name }}</h3>
            <p>{{ trim(collect([$spot->prefecture, $spot->city, $spot->town, $spot->address_line])->filter()->join(' ')) ?: '住所未設定' }}</p>
            @if ($spot->description)
                <p class="spot-card__excerpt">{{ \Illuminate\Support\Str::limit($spot->description, 110) }}</p>
            @endif
            @if ($spot->genres->isNotEmpty() || $spot->tags->isNotEmpty())
                <div class="tag-row">
                    @foreach ($spot->genres->take(2) as $genre)
                        <span>{{ $genre->name }}</span>
                    @endforeach
                    @foreach ($spot->tags->take(2) as $tag)
                        <span>#{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="list-item__meta">
            <span>公開: {{ optional($spot->published_at)->format('Y-m-d') ?: '未設定' }}</span>
            <span>PV: {{ number_format($spot->view_count) }}</span>
            <span>配下: {{ $spot->children_count ?? 0 }}</span>
            <span>{{ $spot->business_hours_text ?: '営業時間未設定' }}</span>
            <a href="{{ route('spots.show', $spot) }}">詳細を見る</a>
        </div>
    </div>
</article>
