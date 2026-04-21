# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 開発環境

Docker Compose による構成。全サービスは以下で起動：

```bash
docker compose up -d
```

| サービス | URL |
|---|---|
| アプリ (nginx) | http://localhost:8021 |
| phpMyAdmin | http://localhost:8022 |
| Mailpit | http://localhost:8035 |
| Vite (HMR) | http://localhost:5185 |
| MySQL | localhost:3321 |

`node` コンテナが `npm install && npm run dev` を自動実行するため、フロントエンドのビルドは手動不要。

## よく使うコマンド

```bash
# PHP artisan（コンテナ内で実行）
docker exec php-plus1-community php artisan migrate
docker exec php-plus1-community php artisan db:seed
docker exec php-plus1-community php artisan tinker

# フロントエンド（ローカルで実行可能）
npm run dev      # Vite 開発サーバー
npm run build    # 本番ビルド

# node コンテナ再起動（Vite config 変更後など）
docker compose restart node
```

## アーキテクチャ概要

### コアドメイン：Spot（拠点）

`Spot` が中心モデル。`parent_id` による自己参照で階層構造（企業→支店など）を表現する。`depth` カラムで階層レベルを管理。URLは `slug` ベース（`getRouteKeyName()`）。

`scopeVisible()` スコープ（`is_public=true` かつ `published_at` が過去）を公開クエリでは必ず使用する。

### 地理・交通データ

`Prefecture` → `Station`（`pref_code` で紐づく）、`RailwayRoute` ↔ `Station`（`railway_route_station` 中間テーブル、`pivot_order` で駅順序を管理）。

**重要**：`railway_routes.pref_codes` はカンマ区切り文字列（例: `"12,13"`）。`whereJsonContains` は使えない。`FIND_IN_SET(?, pref_codes)` を使うこと。

`NearestStationService::syncForSpot()` がスポットの緯度経度から最寄り駅を自動算出して `spot_stations` に保存する。

### ルーティング構成

- `routes/web.php`：公開ページ（`/`, `/spots`, `/spots/{slug}`, `/admin/*`）
- `routes/api.php`：API エンドポイント（`/api/station-picker/*`）

管理画面は `/admin` プレフィックス、名前は `admin.*`。認証ミドルウェアは未設定（開発中）。

### フロントエンド

Blade テンプレート + Tailwind CSS v4 + Vue.js 3。

Vue コンポーネントは `resources/js/components/` に配置。Blade 側では `data-component="コンポーネント名"` 属性を持つ要素に `app.js` が自動マウントする。

```html
{{-- 使用例 --}}
<div data-component="station-picker"></div>
```

`StationPicker.vue` は都道府県→路線→駅の3ステップ選択UI。選択完了時に `@station-selected` イベントを emit する（`{ id, station_name, line_name, operator_name, pref_name, pref_code }`）。

## StationPicker コンポーネント利用ガイド

### 基本的な使い方（Blade）

`@vite` を読み込んでいる任意の Blade テンプレートで使用可能。

```html
<div data-component="station-picker"></div>
```

`app.js` が `data-component="station-picker"` を持つ全要素に自動マウントする。同一ページに複数設置しても動作する。

### emit イベント

駅を選択すると `station-selected` イベントが発火する。Blade 直埋めの場合は `app.js` 側でカスタムイベントとして受け取る。

```js
// app.js でのイベント受け取り例
document.querySelectorAll('[data-component="station-picker"]').forEach((el) => {
    const app = createApp(StationPicker);
    app.mount(el);

    el.addEventListener('station-selected', (e) => {
        console.log(e.detail); // { id, station_name, line_name, operator_name, pref_name, pref_code }
    });
});
```

emit されるオブジェクトの型：

| フィールド | 内容 |
|---|---|
| `id` | stations.id |
| `station_name` | 駅名（例: 千葉） |
| `line_name` | 路線名（例: 総武線） |
| `operator_name` | 事業者名（例: JR東日本） |
| `pref_name` | 都道府県名（例: 千葉県） |
| `pref_code` | 都道府県コード（例: 12） |

### 別の Vue コンポーネント内で使う場合

```vue
<script setup>
import StationPicker from '@/components/StationPicker.vue';

function onStationSelected(station) {
    console.log(station.station_name); // 選択した駅名
}
</script>

<template>
    <StationPicker @station-selected="onStationSelected" />
</template>
```

### フォームの hidden input と連携する例

```html
<form method="POST" action="/search">
    <input type="hidden" id="station-id" name="station_id">
    <input type="hidden" id="station-name" name="station_name">
    <div data-component="station-picker"></div>
    <button type="submit">この駅で検索</button>
</form>

<script>
document.querySelector('[data-component="station-picker"]')
    ?.addEventListener('station-selected', (e) => {
        document.getElementById('station-id').value = e.detail.id;
        document.getElementById('station-name').value = e.detail.station_name;
    });
</script>
```

### API エンドポイント

コンポーネントが内部で使用する API。直接呼び出すことも可能。

| エンドポイント | レスポンス |
|---|---|
| `GET /api/station-picker/prefectures` | 地方グループ別の都道府県一覧 |
| `GET /api/station-picker/railways?pref_code={code}` | 都道府県内の路線一覧 |
| `GET /api/station-picker/stations?railway_route_id={id}` | 路線上の駅一覧（pivot_order 順） |

### 検索（SpotController）

`area` パラメータのプレフィックスで検索方法を切り替える：
- `[駅] 駅名` → `spot_stations` + `station_near_stations`（徒歩15分以内）で絞り込み
- `[路線] 路線名` → 路線上の全駅で絞り込み
- それ以外 → `prefecture` / `city` / `town` の LIKE 検索
