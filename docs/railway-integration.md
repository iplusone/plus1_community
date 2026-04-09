# 鉄道データ連携 設計・実装計画

## 1. 概要

スポットに駅・路線を紐づけることで、「住所軸」に加えて「交通軸」での検索・表示・回遊を実現する。

**一言で定義:**
> スポットの位置情報から最寄り駅を自動算出し、駅・路線軸での検索と表示に利用する。

---

## 2. 確定した要件

### 2-1. スポットと駅の関係

| 項目 | 決定 |
|------|------|
| 多重度 | 1 スポット : 複数駅（多対多） |
| 駅の設定方法 | 自動取得（登録時・住所変更時） |
| 手動修正 | 自動取得後に手動修正可能 |
| 取得件数 | 近い順に上位 N 件（目安 5 駅以内） |
| 徒歩時間 | 距離から自動計算（80m = 1 分） |

### 2-2. エリア検索

| 項目 | 決定 |
|------|------|
| 入力数 | 単一入力（1 フォーム） |
| 入力補助 | サジェスト（部分一致候補表示） |
| 対象 | 住所（都道府県・市区町村・町名） + 駅 + 路線 を統合 |
| 内部処理 | 入力内容に応じて検索対象を自動判定 |
| 近隣駅展開 | 駅指定時は `station_near_stations` を使い周辺駅もスコープに含める |

### 2-3. 表示

- スポット詳細ページのアクセス情報に最寄り駅（複数・近い順）を表示
- 各駅の徒歩分数を表示
- スポットごとに表示対象を徒歩上限で絞り込み、既定値は `30` 分
- 同じ駅・同じ路線のスポット一覧へのリンク（回遊）

---

## 3. 既存鉄道データ（iplusone_wordpress_laravel_admin より移植）

`iplusone_wordpress_laravel_admin` プロジェクトに実装済み・本番データ投入済みのテーブル。

### テーブル構成（確定）

```
stations                 駅
  id
  station_name           駅名
  wikipedia_url          (nullable)
  line_name              路線名（非正規化、参考値）
  operator_name          鉄道会社名（非正規化、参考値）
  pref_code              都道府県コード
  longitude DECIMAL(10,6) 経度
  latitude  DECIMAL(10,6) 緯度
  location_confirmed     緯度経度確認済みフラグ
  created_at / updated_at

railway_routes           路線
  id
  line_name              路線名
  operator_name          鉄道会社名
  pref_codes             都道府県コード（カンマ区切り複数可）
  geometry               GeoJSON（nullable）
  created_at / updated_at

railway_route_station    路線 × 駅（中間）
  id
  railway_route_id  → railway_routes.id
  station_id        → stations.id
  pivot_order       駅の並び順
  created_at / updated_at

station_near_stations    近隣駅
  id
  station_id        → stations.id
  near_station_id   → stations.id
  distance_km       FLOAT（直線距離 km）
  walking_minutes   INT（徒歩時間 分）
  created_at / updated_at
  UNIQUE(station_id, near_station_id)
```

### 取り込み方法

`iplusone_wordpress_laravel_admin` の本番 DB から上記 4 テーブルをダンプし、本プロジェクトの DB にインポートする。

```bash
# 元プロジェクトでダンプ
mysqldump -u [user] -p [db] stations railway_routes railway_route_station station_near_stations \
  > railway_data.sql

# 本プロジェクトの DB にインポート
docker compose exec -T mysql mysql -ularavel -plaravel laravel < railway_data.sql
```

マイグレーションも同様に移植してスキーマを定義する。

---

## 4. 新規テーブル

### `spot_stations`（スポット × 駅 中間テーブル）

```sql
id                  BIGINT UNSIGNED PK
spot_id             BIGINT UNSIGNED FK → spots.id
station_id          BIGINT UNSIGNED FK → stations.id
distance_km         DECIMAL(6,3)      -- 直線距離
walking_minutes     SMALLINT UNSIGNED -- distance_km / 0.08 で自動計算
sort_order          TINYINT UNSIGNED  -- 近い順に 1 から
created_at
updated_at

INDEX (spot_id)
INDEX (station_id)
UNIQUE (spot_id, station_id)
```

---

## 5. 既存テーブルの変更

### `spots` に位置情報を追加

```sql
latitude   DECIMAL(9,6) NULL  -- 緯度
longitude  DECIMAL(9,6) NULL  -- 経度
```

> 位置情報がないと最寄り駅の自動算出ができない。スポット登録フォームへの入力欄追加も必要。

---

## 6. 最寄り駅の自動算出ロジック

### トリガー

- スポット作成時（`latitude` / `longitude` が設定されている場合）
- `latitude` または `longitude` が変更された場合

### 算出アルゴリズム

1. スポットの緯度・経度を取得
2. `stations` テーブルから Haversine 距離で近い駅を取得
3. 近い順に上位 N 件（定数: `NEAREST_STATION_LIMIT = 5`）を選択
4. 徒歩分数 = `CEIL(distance_m / 80)`（端数切り上げ）
5. `spot_stations` を `upsert`（既存データは一旦削除して再生成）

### 実装位置

`SpotObserver::saved()` に追加（既存の `SpotSearchDocument::syncForSpot()` と同じタイミング）

```php
// SpotObserver::saved() に追加
if ($spot->wasChanged(['latitude', 'longitude']) || $spot->wasRecentlyCreated) {
    NearestStationService::syncForSpot($spot);
}
```

### サービスクラス

`app/Services/NearestStationService.php`

```php
public static function syncForSpot(Spot $spot, int $limit = 5): void
{
    // latitude/longitude がなければスキップ
    if (! $spot->latitude || ! $spot->longitude) {
        return;
    }

    // Haversine 式で近い駅を limit 件取得
    // distance_km → walking_minutes = CEIL(distance_km * 1000 / 80)
    // spot_stations を再生成
}
```

---

## 7. 検索への影響

### エリアサジェスト拡張

`SearchSuggestionController` に駅・路線サジェストを追加。

```
GET /suggestions/area?q=渋谷
→ ["渋谷区", "[駅] 渋谷（東急）", "[路線] 東急東横線（東急）"]
```

現状は文字列ベースで運用し、将来的に検索インデックスや ID ベース値へ移行する方針。

### 検索処理の拡張（`SpotController::applyFilters()`）

| 入力パターン | 検索処理 |
|------------|---------|
| `area=渋谷区`（住所） | `spots.city LIKE '%渋谷区%'` |
| `area=[駅] 渋谷`（駅文字列） | `spot_stations.station_id IN (渋谷駅 + 近隣駅ID...)` |
| `area=[路線] 東急東横線`（路線文字列） | `spot_stations.station_id IN (railway_route_station で絞った全駅ID)` |

近隣駅の範囲は `station_near_stations.walking_minutes <= 15` を目安にする（調整可能）。

`stations.station_name`、`railway_routes.line_name`、`railway_routes.operator_name` を対象に部分一致検索。

---

## 8. 表示への影響

### スポット詳細ページ

```
最寄り駅
  渋谷駅（東急東横線）   徒歩 3 分
  代官山駅（東急東横線） 徒歩 8 分
  恵比寿駅（JR山手線）  徒歩 12 分
```

### 回遊リンク

- 「渋谷駅のスポット一覧」→ `/spots?area=[駅] 渋谷`
- 「東急東横線沿線のスポット一覧」→ `/spots?area=[路線] 東急東横線`

---

## 9. 実装フェーズ

### Step 1: 鉄道データ移植 ✅ 完了

- [x] `iplusone_wordpress_laravel_admin` の本番 DB から 4 テーブルをダンプ
  - `stations`, `railway_routes`, `railway_route_station`, `station_near_stations`
- [x] 同プロジェクトのマイグレーションファイル（4 本）を本プロジェクトにコピー
- [x] ローカル DB にダンプをインポートして動作確認
- [x] `Station` / `RailwayRoute` モデルを本プロジェクトに作成

### Step 2: テーブル追加 ✅ 完了

- [x] `spots` に `latitude` / `longitude` カラムを追加するマイグレーション
- [x] `spot_stations` テーブルのマイグレーション
- [x] `Spot` モデルに `stations()` リレーション追加
- [x] `SpotStation` モデル作成

### Step 3: 最寄り駅自動算出 ✅ 完了

- [x] `NearestStationService` 実装（Haversine 距離計算、上位 5 駅、80m=1分）
- [x] `SpotObserver::saved()` に組み込み（latitude/longitude 変更時のみ再計算）
- [x] スポット登録フォームに緯度・経度入力欄を追加
- [x] `AdminSpotController` バリデーション追加

### Step 4: 管理画面拡張 ✅ 完了

- [x] `admin/spots/{spot}/stations` — 最寄り駅一覧・削除・手動追加
- [x] 「位置情報から再算出」ボタン
- [x] sub-nav に「最寄り駅」タブ追加
- [x] スポット基本情報で「最寄り駅の徒歩表示上限（分）」を設定可能

### Step 5: 検索拡張 ✅ 完了

- [x] エリアサジェスト API に駅・路線を追加（`/suggestions/area`）
- [x] `SpotController::applyFilters()` に駅・路線検索を追加
- [x] 検索フォームの `prefecture` 入力を `area` 統合入力に置き換え
- [x] 駅指定時に近隣駅も含める検索を追加

### Step 6: 表示拡張 ✅ 完了

- [x] スポット詳細の最寄り駅表示（地図セクション内 + サイドバー）
- [x] 同駅・同路線の回遊リンク（`/spots?area=[駅] 駅名` / `?area=[路線] 路線名`）

---

## 10. 未確定事項

| 項目 | 内容 | 優先度 |
|------|------|--------|
| サジェスト値の表現 | 文字列運用を続けるか、将来 ID 化するか | **中** |
| 近隣駅の展開範囲 | 徒歩何分まで含めるか（現案 15 分） | 中 |
| 位置情報の入力方法 | 手入力 / 住所から自動ジオコーディング | 中 |
| 緯度経度の自動取得 | 住所 → Geocoding API の導入有無 | 低 |
