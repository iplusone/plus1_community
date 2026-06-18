# 国土数値情報（MLIT）スポットインポート

国土交通省・[国土数値情報](https://nlftp.mlit.go.jp/ksj/)から取得したGeoJSONデータを `mlit_spots` テーブルにインポートする仕組みの解説。

---

## テーブル構成

### `mlit_datasets`（データセット管理マスタ）

インポート対象のデータ種別と管理情報を保持する。

| カラム | 型 | 説明 |
|---|---|---|
| `code` | varchar(10) UNIQUE | データ識別コード（例: `P11`） |
| `name` | varchar(100) | データ名（例: バス停留所） |
| `category` | varchar(50) | カテゴリ（後述） |
| `license` | varchar(100) | ライセンス（例: CC BY 4.0） |
| `download_url` | text | 取得元URL |
| `source_year` | varchar(10) | データ年度（例: 2021） |
| `last_imported_at` | timestamp | 最終インポート日時 |
| `record_count` | int unsigned | インポート済み件数 |

### `mlit_spots`（インポート済みスポット）

全データ種別共通のカラム + データ固有属性を `attributes` JSON に格納する。

| カラム | 型 | 説明 |
|---|---|---|
| `dataset_code` | varchar(10) | `mlit_datasets.code` |
| `source_id` | varchar(100) | 元データの安定識別子（重複防止キー） |
| `category` | varchar(50) | カテゴリ（非正規化） |
| `sub_category` | varchar(100) | サブカテゴリ（例: `bus_stop`） |
| `name` | varchar(255) | 施設名 |
| `pref_code` | char(2) | 都道府県コード（例: `12`） |
| `admin_area_code` | varchar(10) | 市区町村コード（5桁） |
| `address` | varchar(500) | 所在地 |
| `latitude` | decimal(10,7) | 緯度 |
| `longitude` | decimal(10,7) | 経度 |
| `attributes` | json | データ種別固有の追加属性 |
| `source_year` | varchar(10) | データ年度 |
| `imported_at` | timestamp | インポート日時 |

**ユニーク制約**: `(dataset_code, source_id)` — 再インポート時はupsertで差分更新される。

**インデックス**: `pref_code` / `admin_area_code` / `(category, sub_category)` / `(latitude, longitude)`

---

## カテゴリ・サブカテゴリ一覧

| category | sub_category | コード | データ名 |
|---|---|---|---|
| `transport` | `bus_stop` | P11 | バス停留所 |
| `medical` | `medical_facility` | P04 | 医療機関 |
| `welfare` | `welfare_facility` | P14 | 福祉施設 |
| `education` | `school` | A27 | 学校 |
| `disaster` | `evacuation_facility` | P20 | 避難施設 |
| `industry` | `roadside_station` | P35 | 道の駅 |
| `public` | `fire_station` | P17 | 消防署 |
| `public` | `police` | P18 | 警察署 |
| `public` | `post_office` | P30 | 郵便局 |
| `tourism` | `tourism_resource` | P12 | 観光資源 |

---

## データ取得方法

[国土数値情報ダウンロードサービス](https://nlftp.mlit.go.jp/ksj/)から各データを取得する。

- 形式: **GeoJSON** を選択（Shape / XML / GeoJSON が選べる場合はGeoJSONが最も扱いやすい）
- 単位: 都道府県単位または全国単位（データ種別によって異なる）
- ダウンロード形式: ZIP（中に `.geojson` ファイルが入っている）

### 各データの取得ページ

| コード | データ名 | 取得ページ |
|---|---|---|
| P11 | バス停留所 | https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P11-v3_1.html |
| P04 | 医療機関 | https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P04-v3_1.html |
| P14 | 福祉施設 | https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P14-v2_1.html |
| A27 | 学校 | https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-A27-v2_1.html |
| P20 | 避難施設 | https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P20-v2_4.html |
| P35 | 道の駅 | https://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-P35-v1_1.html |
| P17 | 消防署 | https://nlftp.mlit.go.jp/ksj/ |
| P18 | 警察署 | https://nlftp.mlit.go.jp/ksj/ |
| P30 | 郵便局 | https://nlftp.mlit.go.jp/ksj/ |
| P12 | 観光資源 | https://nlftp.mlit.go.jp/ksj/ |

---

## インポート手順

### 1. マイグレーション・シーダー

```bash
# テーブル作成
docker exec php-plus1-community php artisan migrate

# データセットマスタ投入
docker exec php-plus1-community php artisan db:seed --class=MlitDatasetsSeeder
```

### 2. ファイルを配置する

**方法A: `--file` で直接指定**（任意のパスに置いてOK）

**方法B: 規定ディレクトリに置いて自動検索**

```
storage/app/mlit/
  P11/    ← バス停留所のGeoJSON / ZIPを置く
  P04/
  P14/
  ...
```

方法Bを使うと `--file` 省略でディレクトリ内の最新ファイルを自動検出する。

### 3. インポート実行

```bash
# GeoJSONファイルを直接指定
docker exec php-plus1-community php artisan import:mlit P11 --file=/path/to/P11_14-2010.geojson

# ZIPファイルも直接指定可能（内部で自動展開）
docker exec php-plus1-community php artisan import:mlit P11 --file=/path/to/P11_14-2010.zip

# storage/app/mlit/P11/ に置いた場合（--file 省略）
docker exec php-plus1-community php artisan import:mlit P11

# 別ディレクトリを指定
docker exec php-plus1-community php artisan import:mlit P11 --dir=/data/mlit

# dry-run: DBに書き込まず件数のみ確認
docker exec php-plus1-community php artisan import:mlit P11 --file=/path/to/file.geojson --dry-run
```

---

## `attributes` JSON の構造

データ種別ごとに格納される属性。

### P11 バス停留所

```json
{
  "bus_type": 1,
  "operators": ["関東バス", "西武バス"],
  "routes": ["吉祥寺駅北口行", "荻窪駅行"]
}
```

### P14 福祉施設

```json
{
  "large_category": 1,
  "medium_category": 3,
  "small_category": 2,
  "manager_code": "01"
}
```

### P20 避難施設

```json
{
  "facility_type": "小学校",
  "capacity": 500,
  "area_m2": 2400.0,
  "earthquake": true,
  "tsunami": false,
  "flood": true,
  "volcano": false,
  "other": false
}
```

### P35 道の駅

```json
{
  "prefecture": "千葉県",
  "city": "木更津市",
  "urls": ["https://example.com"],
  "facilities": {
    "atm": true,
    "baby_bed": true,
    "restaurant": true,
    "snack_bar": false,
    "lodging": false,
    "hot_spring": false,
    "campsite": false,
    "park": true,
    "observation_deck": false,
    "museum": false,
    "gas_station": true,
    "ev_charger": true,
    "wifi": true,
    "shower": false,
    "experience": false,
    "tourist_info": true,
    "accessible_toilet": true,
    "shop": true
  }
}
```

### P17 消防署

```json
{
  "type_code": 2,
  "type_label": "fire_station"
}
```
`type_label`: `headquarters`（消防本部）/ `fire_station`（消防署）/ `substation`（分署・出張所）

### P12 観光資源

```json
{
  "resource_type": "温泉"
}
```

---

## コードの場所

| ファイル | 役割 |
|---|---|
| `app/Console/Commands/ImportMlitCommand.php` | Artisanコマンド |
| `app/Services/Mlit/AbstractMlitImporter.php` | 基底クラス（ZIP展開・バッチupsert） |
| `app/Services/Mlit/Importers/` | データ種別ごとの変換ロジック |
| `app/Models/MlitDataset.php` | データセットモデル |
| `app/Models/MlitSpot.php` | スポットモデル（`scopeNearby` 等） |
| `database/migrations/2026_06_18_000001_create_mlit_datasets_table.php` | mlit_datasets テーブル |
| `database/migrations/2026_06_18_000002_create_mlit_spots_table.php` | mlit_spots テーブル |
| `database/seeders/MlitDatasetsSeeder.php` | データセットマスタ初期データ |

### 新しいデータ種別を追加する場合

1. `app/Services/Mlit/Importers/` に新しいインポータークラスを作成（`AbstractMlitImporter` を継承）
2. `ImportMlitCommand.php` の `$importers` 配列にコードとクラスを追加
3. `MlitDatasetsSeeder.php` にデータセット情報を追記

---

## ライセンスに関する注意

国土数値情報の多くは **CC BY 4.0** で提供されており、商用利用時は出典表記が必要。データ種別によってライセンスが異なる場合があるため、各データのダウンロードページで必ず確認すること。

> 出典：国土交通省「国土数値情報」(https://nlftp.mlit.go.jp/ksj/)
