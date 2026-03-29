# 実装方針メモ

本ドキュメントは、[ポータルサイト要件定義](./portal-requirements.md) を Laravel 実装へ落とすための初期整理である。

## 1. 実装の考え方

- 管理データと検索データを分離する
- スポットをシステムの中心概念として扱う
- 公開画面と管理画面で責務を分ける
- 検索性能を優先し、検索用データは非正規化前提で設計する

## 2. 主要ドメイン

### スポット管理

- スポット基本情報
- 親子階層
- 公開状態
- 公開予約
- サムネイル

### 管理者権限

- ユーザー
- スポット管理者紐付け
- 階層ベースの管理可能範囲

### スポット詳細情報

- アクセス情報
- 説明
- サービス・料金
- メディア
- スタッフ
- クーポン

### 外部コンテンツ連携

- スポットごとの WordPress 連携情報
- ニュース / ブログ記事の取り込みまたは参照

### 検索

- 地域
- ジャンル
- タグ
- 検索用インデックス
- PV ベースの人気順

## 3. 初期テーブル候補

管理系:

- `spots`
- `spot_admins`
- `users`
- `spot_media`
- `spot_staff`
- `spot_coupons`
- `spot_services`
- `spot_menus`
- `spot_business_hours`
- `spot_genres`
- `genres`
- `spot_tags`
- `tags`
- `regions`
- `spot_wordpress_sites`

検索系:

- `spot_search_documents`

補助系:

- `spot_page_views`
- `spot_featured_slots`

## 4. `spots` テーブルで最低限持つ項目

- `id`
- `parent_id`
- `depth`
- `name`
- `slug`
- `postal_code`
- `prefecture`
- `city`
- `town`
- `address_line`
- `phone`
- `business_hours_text`
- `holiday_text`
- `access_text`
- `description`
- `features`
- `thumbnail_path`
- `is_public`
- `published_at`
- `view_count`
- `sort_order`

## 5. 検索用ドキュメントの考え方

`spot_search_documents` は 1 スポット 1 レコードを基本とする。

保持候補:

- `spot_id`
- `spot_name`
- `prefecture`
- `city`
- `town`
- `full_address`
- `genre_paths`
- `tag_names`
- `is_public`
- `published_at`
- `view_count`
- `thumbnail_url`

更新タイミング:

- スポット更新時
- 公開状態変更時
- ジャンル / タグ変更時
- サムネイル変更時

## 6. 画面の初期分割

公開側:

- トップページ
- スポット詳細
- 検索結果一覧

管理側:

- ログイン
- スポット一覧
- スポット作成 / 編集
- 配下スポット管理
- メディア管理
- スタッフ管理
- クーポン管理
- WordPress 連携設定

## 7. 実装優先順位案

### Phase 1

- スポット基本 CRUD
- 階層構造
- 公開 / 非公開 / 公開予約
- 公開側スポット詳細

### Phase 2

- 地域 / ジャンル / タグ
- 検索用インデックス
- 検索一覧
- 並び替え

### Phase 3

- スタッフ
- クーポン
- メディア
- おすすめ / 最新 / ランダム

### Phase 4

- WordPress 連携
- PV 集計
- 人気順最適化

## 8. 次に着手するとよい作業

1. ER 図相当のテーブル設計を固める
2. Laravel の migration 設計を作る
3. 管理画面の権限ルールをユースケース単位で整理する
4. 検索インデックス更新方式をイベント駆動にするか決める
