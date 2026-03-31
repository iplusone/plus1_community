@extends('layouts.app')

@section('title', $spot->name . ' - サービス管理')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>サービス管理</h1>
            </div>
            <a class="button-primary" href="{{ route('admin.spots.services.create', $spot) }}">サービス追加</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>タイトル</th>
                        <th>メニュー数</th>
                        <th>表示順</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td>{{ $service->title }}</td>
                            <td>{{ $service->menus->count() }}</td>
                            <td>{{ $service->sort_order }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.spots.services.menus.index', [$spot, $service]) }}">メニュー</a>
                                <a href="{{ route('admin.spots.services.edit', [$spot, $service]) }}">編集</a>
                                <form method="POST" action="{{ route('admin.spots.services.destroy', [$spot, $service]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('削除しますか？')">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">サービスはまだ登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
