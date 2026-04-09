@extends('layouts.app')

@section('title', $spot->exists ? 'スポット編集' : 'スポット作成')

@section('content')
    <section class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>{{ $spot->exists ? 'スポット編集' : 'スポット作成' }}</h1>
            </div>
        </div>

        @if ($spot->exists)
            @include('admin.spots.partials.sub-nav')
        @endif

        @if (! empty($dbWarning))
            <div class="notice-panel compact">
                <p>{{ $dbWarning }}</p>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" class="form-card">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <label>
                <span>親スポット</span>
                <select name="parent_id">
                    <option value="">なし</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $spot->parent_id) == $parent->id)>
                            {{ $parent->hierarchyLabel() }}（第{{ $parent->depth }}階層）
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>スポット名</span>
                <input type="text" name="name" value="{{ old('name', $spot->name) }}" required>
            </label>

            <label>
                <span>スラッグ</span>
                <input type="text" name="slug" value="{{ old('slug', $spot->slug) }}">
            </label>

            <div class="form-grid">
                <label>
                    <span>都道府県</span>
                    <input type="text" name="prefecture" value="{{ old('prefecture', $spot->prefecture) }}">
                </label>
                <label>
                    <span>市区町村</span>
                    <input type="text" name="city" value="{{ old('city', $spot->city) }}">
                </label>
                <label>
                    <span>町名</span>
                    <input type="text" name="town" value="{{ old('town', $spot->town) }}">
                </label>
                <label>
                    <span>郵便番号</span>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $spot->postal_code) }}">
                </label>
            </div>

            <label>
                <span>住所詳細</span>
                <input type="text" name="address_line" value="{{ old('address_line', $spot->address_line) }}">
            </label>

            <label>
                <span>電話番号</span>
                <input type="text" name="phone" value="{{ old('phone', $spot->phone) }}">
            </label>

            <label>
                <span>概要</span>
                <textarea name="description" rows="4">{{ old('description', $spot->description) }}</textarea>
            </label>

            <label>
                <span>特徴</span>
                <textarea name="features" rows="4">{{ old('features', $spot->features) }}</textarea>
            </label>

            <label>
                <span>アクセス</span>
                <textarea name="access_text" rows="3">{{ old('access_text', $spot->access_text) }}</textarea>
            </label>

            <div class="form-grid">
                <label>
                    <span>最寄り駅の徒歩表示上限（分）</span>
                    <input type="number" name="nearest_station_max_walking_minutes"
                           value="{{ old('nearest_station_max_walking_minutes', $spot->nearest_station_max_walking_minutes ?? 30) }}"
                           min="1" max="999">
                </label>
            </div>

            <div class="form-grid">
                <label>
                    <span>営業時間</span>
                    <input type="text" name="business_hours_text" value="{{ old('business_hours_text', $spot->business_hours_text) }}">
                </label>
                <label>
                    <span>定休日</span>
                    <input type="text" name="holiday_text" value="{{ old('holiday_text', $spot->holiday_text) }}">
                </label>
                <label>
                    <span>公開日時</span>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($spot->published_at)->format('Y-m-d\TH:i')) }}">
                </label>
                <label>
                    <span>表示順</span>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $spot->sort_order ?? 0) }}" min="0">
                </label>
            </div>

            <div class="form-grid">
                <label>
                    <span>緯度</span>
                    <input type="number" name="latitude" step="0.000001"
                           value="{{ old('latitude', $spot->latitude) }}"
                           placeholder="例: 35.658581">
                </label>
                <label>
                    <span>経度</span>
                    <input type="number" name="longitude" step="0.000001"
                           value="{{ old('longitude', $spot->longitude) }}"
                           placeholder="例: 139.745433">
                </label>
            </div>

            <label class="checkbox-row">
                <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $spot->is_public))>
                <span>公開する</span>
            </label>

            @if ($errors->any())
                <div class="notice-panel compact error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="hero-actions">
                <button type="submit" class="button-primary">{{ $spot->exists ? '更新する' : '作成する' }}</button>
                <a class="button-secondary" href="{{ route('admin.spots.index') }}">一覧へ戻る</a>
            </div>
        </form>
    </section>
@endsection
