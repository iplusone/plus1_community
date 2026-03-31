@extends('layouts.app')

@section('title', $spot->name . ' - 最寄り駅管理')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>最寄り駅管理</h1>
            </div>
            <form method="POST" action="{{ route('admin.spots.stations.recalculate', $spot) }}">
                @csrf
                <button type="submit" class="button-secondary"
                        @unless($spot->latitude && $spot->longitude) disabled title="緯度・経度が未設定です" @endunless>
                    位置情報から再算出
                </button>
            </form>
        </div>

        @unless($spot->latitude && $spot->longitude)
            <div class="notice-panel compact">
                <p>緯度・経度が未設定のため自動算出できません。
                   <a href="{{ route('admin.spots.edit', $spot) }}">基本情報</a>で位置情報を入力してください。</p>
            </div>
        @endunless

        @if (session('status'))
            <div class="notice-panel compact">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice-panel compact error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- 現在の最寄り駅 --}}
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>順</th>
                        <th>駅名</th>
                        <th>路線 / 会社</th>
                        <th>距離</th>
                        <th>徒歩</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($spotStations as $ss)
                        <tr>
                            <td>{{ $ss->sort_order }}</td>
                            <td>{{ $ss->station->station_name }}</td>
                            <td>
                                {{ $ss->station->line_name ?: '-' }}
                                @if ($ss->station->operator_name)
                                    <small class="muted-text">（{{ $ss->station->operator_name }}）</small>
                                @endif
                            </td>
                            <td>{{ $ss->distance_km ? number_format($ss->distance_km, 2) . ' km' : '-' }}</td>
                            <td>{{ $ss->walking_minutes ? $ss->walking_minutes . ' 分' : '-' }}</td>
                            <td class="table-actions">
                                <form method="POST"
                                      action="{{ route('admin.spots.stations.destroy', [$spot, $ss]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('削除しますか？')">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">最寄り駅が登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 手動追加 --}}
        <div class="section-heading" style="margin-top:2rem">
            <div>
                <h2 style="margin:0;font-size:1.1rem">駅を手動で追加</h2>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.spots.stations.store', $spot) }}" class="form-card">
            @csrf
            <div class="form-grid">
                <label>
                    <span>駅名（部分一致）</span>
                    <input type="text" name="station_name" value="{{ old('station_name') }}"
                           placeholder="例: 渋谷、新宿三丁目" required>
                </label>
                <label>
                    <span>徒歩分数（任意）</span>
                    <input type="number" name="walking_minutes" value="{{ old('walking_minutes') }}"
                           min="1" max="999" placeholder="例: 5">
                </label>
            </div>
            <div class="hero-actions">
                <button type="submit" class="button-primary">追加する</button>
            </div>
        </form>
    </section>
@endsection
