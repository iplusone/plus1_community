@extends('layouts.app')

@section('title', $spot->name . ' - ' . $service->title . ' メニュー管理')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <nav class="breadcrumbs" style="margin-top:.5rem">
            <a href="{{ route('admin.spots.services.index', $spot) }}">サービス一覧</a>
            <span>/</span>
            <span>{{ $service->title }}</span>
            <span>/</span>
            <span>メニュー</span>
        </nav>

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>メニュー管理</h1>
            </div>
            <a class="button-primary" href="{{ route('admin.spots.services.menus.create', [$spot, $service]) }}">メニュー追加</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>価格</th>
                        <th>表示順</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($menus as $menu)
                        <tr>
                            <td>{{ $menu->name }}</td>
                            <td>{{ $menu->price_text ?: '-' }}</td>
                            <td>{{ $menu->sort_order }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.spots.services.menus.edit', [$spot, $service, $menu]) }}">編集</a>
                                <form method="POST"
                                      action="{{ route('admin.spots.services.menus.destroy', [$spot, $service, $menu]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('削除しますか？')">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">メニューはまだ登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
