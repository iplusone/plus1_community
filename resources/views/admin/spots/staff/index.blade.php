@extends('layouts.app')

@section('title', $spot->name . ' - スタッフ管理')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>スタッフ管理</h1>
            </div>
            <a class="button-primary" href="{{ route('admin.spots.staff.create', $spot) }}">スタッフ追加</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>プロフィール</th>
                        <th>表示順</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $member)
                        <tr>
                            <td>{{ $member->name }}</td>
                            <td>{{ Str::limit($member->profile, 40) ?: '-' }}</td>
                            <td>{{ $member->sort_order }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.spots.staff.edit', [$spot, $member]) }}">編集</a>
                                <form method="POST" action="{{ route('admin.spots.staff.destroy', [$spot, $member]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('削除しますか？')">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">スタッフはまだ登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
