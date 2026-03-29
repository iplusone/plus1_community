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
- `slug` は詳細ページ URL 用

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

## 5. migration 作成の優先順

1. `spots`
2. `spot_admins`
3. `regions`
4. `genres`
5. `tags`
6. `spot_genres`
7. `spot_tags`
8. `spot_media`
9. `spot_staff`
10. `spot_coupons`
11. `spot_services`
12. `spot_menus`
13. `spot_wordpress_sites`
14. `spot_featured_slots`
15. `spot_page_views`
16. `spot_search_documents`

## 6. 未確定事項

- 管理者権限をロール名で持つか、可視範囲で持つか
- 地域を完全マスタ化するか、拠点に文字列も持たせるか
- タグサジェストの正規化ルール
- WordPress 記事を都度取得するか、ローカル同期するか
- 検索インデックスを同期更新にするか、キュー更新にするか
