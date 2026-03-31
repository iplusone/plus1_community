@extends('layouts.app')

@section('title', 'スポット管理')

@section('content')
    <section class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>スポット管理</h1>
            </div>
            <a class="button-primary" href="{{ route('admin.spots.create') }}">新規作成</a>
        </div>

        @if ($dbWarning)
            <div class="notice-panel compact">
                <p>{{ $dbWarning }}</p>
            </div>
        @endif

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>階層</th>
                        <th>親</th>
                        <th>公開</th>
                        <th>詳細管理</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($spots as $spot)
                        <tr>
                            <td>{{ $spot->name }}</td>
                            <td>{{ $spot->depth }}</td>
                            <td>{{ $spot->parent?->name ?: '-' }}</td>
                            <td>{{ $spot->is_public ? '公開' : '非公開' }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.spots.staff.index', $spot) }}">スタッフ</a>
                                <a href="{{ route('admin.spots.coupons.index', $spot) }}">クーポン</a>
                                <a href="{{ route('admin.spots.media.index', $spot) }}">メディア</a>
                                <a href="{{ route('admin.spots.services.index', $spot) }}">サービス</a>
                                <a href="{{ route('admin.spots.stations.index', $spot) }}">最寄り駅</a>
                            </td>
                            <td class="table-actions">
                                <a href="{{ route('spots.show', $spot->slug) }}">公開画面</a>
                                <a href="{{ route('admin.spots.edit', $spot) }}">編集</a>
                                <form method="POST" action="{{ route('admin.spots.destroy', $spot) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">スポットはまだありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $spots->links() }}
        </div>
    </section>
@endsection
