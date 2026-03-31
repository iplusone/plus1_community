@extends('layouts.app')

@section('title', $spot->name . ' - クーポン管理')

@section('content')
    <section class="section-block">
        @include('admin.spots.partials.sub-nav')

        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>クーポン管理</h1>
            </div>
            <a class="button-primary" href="{{ route('admin.spots.coupons.create', $spot) }}">クーポン追加</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>タイトル</th>
                        <th>有効期間</th>
                        <th>状態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($coupons as $coupon)
                        <tr>
                            <td>{{ $coupon->title }}</td>
                            <td>
                                {{ optional($coupon->starts_at)->format('Y/m/d') ?: '—' }}
                                〜
                                {{ optional($coupon->expires_at)->format('Y/m/d') ?: '—' }}
                            </td>
                            <td>{{ $coupon->is_active ? '有効' : '無効' }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.spots.coupons.edit', [$spot, $coupon]) }}">編集</a>
                                <form method="POST" action="{{ route('admin.spots.coupons.destroy', [$spot, $coupon]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('削除しますか？')">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">クーポンはまだ登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
