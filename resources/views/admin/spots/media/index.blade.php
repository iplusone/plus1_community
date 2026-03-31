@extends('layouts.app')

@section('title', $spot->name . ' - メディア管理')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="admin-tabs">
            <a href="{{ route('admin.spots.media.index', ['spot' => $spot, 'type' => 'image']) }}"
               @class(['is-active' => $mediaType === 'image'])>画像</a>
            <a href="{{ route('admin.spots.media.index', ['spot' => $spot, 'type' => 'video']) }}"
               @class(['is-active' => $mediaType === 'video'])>動画</a>
        </div>

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>{{ $mediaType === 'image' ? '画像管理' : '動画管理' }}</h1>
            </div>
            <a class="button-primary" href="{{ route('admin.spots.media.create', ['spot' => $spot, 'type' => $mediaType]) }}">
                {{ $mediaType === 'image' ? '画像追加' : '動画追加' }}
            </a>
        </div>

        <div class="active-filters">
            <span>画像 {{ $allMedia->where('type', 'image')->count() }} / 10</span>
            <span>動画 {{ $allMedia->where('type', 'video')->count() }} / 5</span>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>種別</th>
                        <th>パス</th>
                        <th>キャプション</th>
                        <th>表示順</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($media as $item)
                        <tr>
                            <td>{{ $item->type === 'video' ? '動画' : '画像' }}</td>
                            <td>{{ Str::limit($item->path, 40) }}</td>
                            <td>{{ $item->caption ?: '-' }}</td>
                            <td>{{ $item->sort_order }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.spots.media.edit', [$spot, $item]) }}">編集</a>
                                <form method="POST" action="{{ route('admin.spots.media.destroy', [$spot, $item]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('削除しますか？')">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">{{ $mediaType === 'image' ? '画像はまだ登録されていません。' : '動画はまだ登録されていません。' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
