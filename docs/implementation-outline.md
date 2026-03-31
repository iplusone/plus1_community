# 実装方針メモ

本ドキュメントは、[ポータルサイト要件定義](./portal-requirements.md) を Laravel 実装へ落とすための初期整理である。

## 1. 実装の考え方

- 管理データと検索データを分離する
- 拠点(スポット)をシステムの中心概念として扱う
- 公開画面と管理画面で責務を分ける
- 検索性能を優先し、検索用データは非正規化前提で設計する

## 2. 主要ドメイン

### 拠点管理

- 拠点基本情報
- 親子階層
- 公開状態
- 公開予約
- サムネイル

### 管理者権限

- ユーザー
- 企業
- 企業招待 / 初回登録
- 拠点管理者紐付け
- 階層ベースの管理可能範囲

### 拠点詳細情報

- アクセス情報
- 説明
- サービス・料金
- メディア
- スタッフ
- クーポン

### 外部コンテンツ連携

- 拠点ごとの WordPress 連携情報
- ニュース / ブログ記事の取り込みまたは参照

### 検索

- 地域
- ジャンル
- タグ
- 検索用インデックス
- PV ベースの人気順

## 3. 初期テーブル候補

管理系:

- `companies`
- `company_registrations`
- `company_user_invitations`
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

## 4.1 企業・承認まわりで最低限必要なテーブル

### `companies`

- 企業本体
- 初回登録後、承認対象となる管理単位

候補カラム:

- `id`
- `name`
- `slug`
- `status`
- `approved_at`
- `approved_by`
- `created_at`
- `updated_at`

### `company_registrations`

- 初回企業登録申請
- メール登録から本登録完了までを管理

候補カラム:

- `id`
- `email`
- `token`
- `company_name`
- `applicant_name`
- `status`
- `email_verified_at`
- `submitted_at`
- `reviewed_at`
- `review_note`
- `created_at`
- `updated_at`

### `company_user_invitations`

- 企業管理者・拠点管理者の招待
- 招待メールベースで登録導線を管理

候補カラム:

- `id`
- `company_id`
- `spot_id`
- `email`
- `token`
- `role_scope`
- `status`
- `invited_by`
- `accepted_at`
- `expires_at`
- `created_at`
- `updated_at`

## 5. 検索用ドキュメントの考え方

`spot_search_documents` は 1 拠点(スポット) 1 レコードを基本とする。

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

- 拠点更新時
- 公開状態変更時
- ジャンル / タグ変更時
- サムネイル変更時

## 6. 画面の初期分割

公開側:

- トップページ
- 拠点詳細
- 検索結果一覧

管理側:

- ログイン
- 拠点一覧
- 拠点作成 / 編集
- 配下拠点管理
- メディア管理
- スタッフ管理
- クーポン管理
- WordPress 連携設定

## 7. 実装優先順位案

### Phase 1 ✅ 完了

- 拠点基本 CRUD（公開・管理画面）
- 階層構造（parent_id / depth 自動計算）
- 公開 / 非公開 / 公開予約（is_public + published_at）
- 公開側拠点詳細（営業時間・サービス・メニュー・メディア・スタッフ・クーポン）

### Phase 2 ✅ 完了（地域マスタ活用は除く）

- ジャンル / タグ（マスタ・中間テーブル・検索フィルター）
- 検索用インデックステーブル（spot_search_documents）
- 検索一覧（キーワード / エリア / ジャンル / タグ / カード・リスト切替）
- 並び替え（最新順 / 人気順）
- 検索サジェスト API（ジャンル・タグ・エリア）
- ⚠️ 地域マスタ（regions テーブルあり・検索 UI での利用は未実装）

### Phase 3 ✅ 完了

- ✅ トップページ（おすすめ / 最新 / ランダム各 10 件）
- ✅ SpotFeaturedSlot テーブル・モデル（トップおすすめ枠）
- ✅ スタッフ・クーポン・メディア・サービス・メニューの管理画面 CRUD
- ✅ SpotSearchDocument 自動同期（SpotObserver → 拠点保存・削除時に自動更新）
- ✅ `companies` テーブルと `users.company_id` / `spots.company_id`
- ✅ 親スポット候補の組織内制限と 5 階層超過防止

### Phase 4 ⏳ 一部着手

- WordPress 連携（テーブル・モデルあり、API 実装未着手）
- PV 集計（spot_page_views テーブルあり、集計ロジック未実装）
- 人気順最適化（PV 集計完了後に対応）

### Phase 5 ⏳ 進行中（鉄道データ連携）

詳細設計: [docs/railway-integration.md](./railway-integration.md)

- ✅ spots に `latitude` / `longitude` 追加
- ✅ `spot_stations` 中間テーブル（スポット × 駅・徒歩分数）
- ✅ 最寄り駅の自動算出（NearestStationService）
- ✅ エリア検索を住所 + 駅 + 路線の統合入力に拡張
- ✅ スポット詳細に最寄り駅表示・回遊リンク
- ✅ 駅指定時に近隣駅も含める検索
- ⏳ サジェスト値の ID 化・検索インデックス最適化は未着手

**前提:** 鉄道テーブル（railway_companies / railway_lines / stations / station_near_stations）の DB 取り込みが完了していること

## 8. 次に着手するとよい作業

1. 企業・承認フロー実装（`company_registrations` / `company_user_invitations` と初回登録・招待フロー）
2. 管理者権限制御（`spot_admins` を使ったアクセス制限・ログイン認証の実装）
3. PV 集計ロジック実装（`spot_page_views` → `view_count` 集計・人気順最適化）
4. WordPress 連携 API 実装（`spot_wordpress_sites` テーブルを活用したコンテンツ取り込み）
5. 地域マスタを検索 UI に統合（現在の文字列フィルターから `regions` 参照へ）
6. 鉄道検索のインデックス最適化（将来の ID ベース検索や検索専用データへの反映）
