# データモデル初期案

本ドキュメントは、[ポータルサイト要件定義](./portal-requirements.md) と [実装方針メモ](./implementation-outline.md) をもとにした、Laravel 向けの初期データモデル案である。

## 1. 設計の前提

- 拠点(スポット)が最上位の中核エンティティ
- 管理用データと検索用データは分離
- 拠点(スポット)は最大 5 階層の自己参照構造
- 検索は `spot_search_documents` を中心に実行
- WordPress は拠点ごとに任意で 1 件だけ紐づく

## 2. エンティティ一覧

### 中核

- `companies`
- `company_registrations`
- `company_user_invitations`
- `spots`
- `users`
- `spot_admins`

### 詳細情報

- `spot_business_hours`
- `spot_services`
- `spot_menus`
- `spot_media`
- `spot_staff`
- `spot_coupons`

### 分類

- `regions`
- `genres`
- `tags`
- `spot_genres`
- `spot_tags`

### 公開・集計

- `spot_featured_slots`
- `spot_page_views`
- `spot_search_documents`

### 外部連携

- `spot_wordpress_sites`

## 3. テーブル概要

### `spots`

拠点(スポット)本体。

主なカラム候補:

- `id`
- `company_id`
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
- `description`
- `features`
- `access_text`
- `business_hours_text`
- `holiday_text`
- `thumbnail_path`
- `is_public`
- `published_at`
- `view_count`
- `created_at`
- `updated_at`

備考:

- `parent_id` により自己参照
- `depth` は検索や表示制御を簡単にするため冗長保持
- `slug` は詳細ページ URL 用（重複時は自動でカウンターを付加）
- `company_id` は実装済み
- `latitude` / `longitude` により最寄り駅の自動算出に対応

### `companies`

企業本体。

主なカラム候補:

- `id`
- `name`
- `slug`
- `status`
- `approved_at`
- `approved_by`
- `created_at`
- `updated_at`

備考:

- `status` は `pre_registered`, `email_verified`, `pending_approval`, `approved`, `rejected` を想定
- 最上位管理者は企業承認時に確定する

### `company_registrations`

企業の初回登録申請。

主なカラム候補:

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

備考:

- メールアドレス登録から本登録、運営審査までを管理
- 申請承認後に `companies` と最上位管理者ユーザを生成する

### `company_user_invitations`

企業配下ユーザの招待。

主なカラム候補:

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

備考:

- 最上位管理者が配下拠点(スポット)の管理者をメールで招待する
- `spot_id` があれば拠点単位招待、なければ企業全体管理招待として扱える

### `users`

管理者ユーザー本体。

既存 Laravel 標準テーブルをベースに利用する。

追加候補:

- `display_name`
- `is_active`

### `spot_admins`

拠点(スポット)と管理者の中間テーブル。

主なカラム候補:

- `id`
- `spot_id`
- `user_id`
- `role_scope`
- `company_id`
- `created_at`
- `updated_at`

備考:

- `role_scope` は `self`, `self_and_descendants`, `all_descendants` のような管理範囲表現を検討
- 実際の権限制御は拠点階層と組み合わせて判定

### `spot_business_hours`

営業時間・定休日の詳細保持。

主なカラム候補:

- `id`
- `spot_id`
- `day_of_week`
- `opens_at`
- `closes_at`
- `is_closed`
- `note`

備考:

- 画面表示簡便のため `spots.business_hours_text` も併用可

### `spot_services`

サービスのまとまり。

主なカラム候補:

- `id`
- `spot_id`
- `title`
- `description`
- `sort_order`

### `spot_menus`

メニュー・料金。

主なカラム候補:

- `id`
- `spot_id`
- `service_id`
- `name`
- `description`
- `price_text`
- `sort_order`

### `spot_media`

画像・動画。

主なカラム候補:

- `id`
- `spot_id`
- `type`
- `path`
- `thumbnail_path`
- `caption`
- `sort_order`

備考:

- `type` は `image`, `video`

### `spot_staff`

スタッフ情報。

主なカラム候補:

- `id`
- `spot_id`
- `name`
- `profile`
- `image_path`
- `sort_order`

### `spot_coupons`

クーポン情報。

主なカラム候補:

- `id`
- `spot_id`
- `title`
- `content`
- `conditions`
- `starts_at`
- `expires_at`
- `is_active`

### `regions`

地域マスタ。

主なカラム候補:

- `id`
- `parent_id`
- `level`
- `name`
- `code`
- `sort_order`

備考:

- `level` は `prefecture`, `city`, `town`

### `genres`

ジャンルマスタ。

主なカラム候補:

- `id`
- `parent_id`
- `depth`
- `name`
- `slug`
- `sort_order`

備考:

- 最大 3 階層

### `tags`

タグマスタ。

主なカラム候補:

- `id`
- `name`
- `slug`
- `usage_count`

### `spot_genres`

拠点(スポット)とジャンルの紐付け。

主なカラム候補:

- `id`
- `spot_id`
- `genre_id`

備考:

- 1 拠点(スポット)最大 3 件の制約をアプリ側で担保

### `spot_tags`

拠点(スポット)とタグの紐付け。

主なカラム候補:

- `id`
- `spot_id`
- `tag_id`

### `spot_wordpress_sites`

WordPress 連携設定。

主なカラム候補:

- `id`
- `spot_id`
- `base_url`
- `api_base_url`
- `username`
- `application_password`
- `is_active`
- `last_synced_at`

備考:

- 1 拠点(スポット) 1 件制約

### `spot_featured_slots`

トップページおすすめ表示用。

主なカラム候補:

- `id`
- `spot_id`
- `slot_type`
- `sort_order`
- `starts_at`
- `ends_at`

備考:

- `slot_type` は `featured`

### `spot_page_views`

PV 集計の元データ。

主なカラム候補:

- `id`
- `spot_id`
- `viewed_on`
- `count`

### `spot_search_documents`

検索専用ドキュメント。

主なカラム候補:

- `id`
- `spot_id`
- `spot_name`
- `prefecture`
- `city`
- `town`
- `full_address`
- `genre_names`
- `genre_paths`
- `tag_names`
- `is_public`
- `published_at`
- `view_count`
- `thumbnail_url`
- `updated_at`

備考:

- 1 拠点(スポット) 1 レコード
- 一覧表示に必要な値を持たせる
- JOIN を避けるため配列的情報は JSON 文字列保持も候補

## 4. リレーション概要

- `companies` 1:N `spots`
- `companies` 1:N `company_user_invitations`
- `spots` 1:N `spots`（親子）
- `spots` N:M `users` through `spot_admins`
- `spots` 1:N `spot_media`
- `spots` 1:N `spot_staff`
- `spots` 1:N `spot_coupons`
- `spots` 1:N `spot_services`
- `spots` 1:N `spot_menus`
- `spots` N:M `genres` through `spot_genres`
- `spots` N:M `tags` through `spot_tags`
- `spots` 1:1 `spot_wordpress_sites`
- `spots` 1:1 `spot_search_documents`

## 5. migration 実装状況

以下は 2026-03-31 時点の実装状況。

| テーブル | 状況 |
|---------|------|
| `spots` | ✅ 実装済み |
| `spot_admins` | ✅ 実装済み |
| `regions` | ✅ 実装済み（検索 UI での利用は未実装） |
| `genres` | ✅ 実装済み |
| `tags` | ✅ 実装済み |
| `spot_genres` | ✅ 実装済み |
| `spot_tags` | ✅ 実装済み |
| `spot_business_hours` | ✅ 実装済み |
| `spot_services` | ✅ 実装済み |
| `spot_menus` | ✅ 実装済み |
| `spot_media` | ✅ 実装済み |
| `spot_staff` | ✅ 実装済み |
| `spot_coupons` | ✅ 実装済み |
| `spot_wordpress_sites` | ✅ 実装済み（API 連携は未実装） |
| `spot_featured_slots` | ✅ 実装済み |
| `spot_page_views` | ✅ 実装済み（集計ロジックは未実装） |
| `spot_search_documents` | ✅ 実装済み（SpotObserver で同期） |
| `companies` | ✅ 実装済み |
| `company_registrations` | ❌ 未実装 |
| `company_user_invitations` | ❌ 未実装 |
| `stations` | ✅ 実装済み |
| `railway_routes` | ✅ 実装済み |
| `railway_route_station` | ✅ 実装済み |
| `station_near_stations` | ✅ 実装済み |
| `spot_stations` | ✅ 実装済み |

### 実装済みとドラフトの主な差分

- `users.company_id` / `users.created_by_user_id` は実装済み
- `spots.company_id` は実装済み
- `spot_menus` の外部キー名は `spot_service_id`（ドラフトの `service_id` と相違）
- エリア検索は `regions` ではなく、現状は文字列 + 駅 + 路線の統合検索
- 鉄道検索は文字列ベースで運用中で、将来インデックス最適化を想定
- `users` への `display_name` / `is_active` 追加は未実施

## 6. 未確定事項

- 企業登録申請と `companies` の責務分離をどこまで厳密にするか
- 管理者権限をロール名で持つか、可視範囲で持つか（spot_admins.role_scope の定義）
- 地域を完全マスタ化するか、拠点に文字列も持たせるか（現状は文字列のみ）
- WordPress 記事を都度取得するか、ローカル同期するか
- 検索インデックス（spot_search_documents）の同期方式（イベント駆動 vs キュー駆動）
