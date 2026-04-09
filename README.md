# Plus1 Community

拠点(スポット)ベースのポータルプラットフォームです。企業傘下の店舗・支社・営業所などの拠点が独立したページを持ち、情報発信・検索・集客を実現します。

## この基盤の売り

- 組織が持つ複数拠点を、1つの公開ポータルとしてまとめて見せられる
- 拠点ごとに独立した詳細ページを持ち、基本情報、サービス、スタッフ、クーポン、メディア、最寄り駅まで届けられる
- キーワード、地域、ジャンル、タグ、駅、路線から横断検索できる
- 組織階層と拠点階層を前提に、将来の権限制御や拠点運用へつなげやすい
- 既存データを取り込んで、短期間で「見られるポータル」として立ち上げやすい

## 開発時の進め方

- 既に合意済みの方針に沿う軽微なUI修正、バグ修正、テスト追加、ドキュメント更新は、都度確認待ちにせずそのまま進める
- 確認が必要なのは、データ構造変更、既存挙動へ大きく影響する変更、運用方針が分かれる判断、本番データへ直接影響する作業に絞る

## 技術スタック

- PHP 8.2+
- Laravel 12
- Vite 7
- Tailwind CSS 4
- PHPUnit 11
- Docker Compose

## できること

- `/` でトップページを表示（おすすめ・最新・ランダム各 10 件）
- `/` でトップページを表示（おすすめ・最新・ランダム各 10 件、登録済み画像をカードサムネイルとして表示）
- `/spots` でスポット検索・一覧（キーワード / エリア / ジャンル / タグ / ソート / カード・リスト切替、登録済み画像をカードサムネイルとして表示）
- `/spots/{slug}` でスポット詳細（基本情報・営業時間・サービス・メニュー・画像ギャラリー・動画・スタッフ・クーポン・最寄り駅）
- `/admin/spots` で管理画面（スポット CRUD・階層管理・スポット名 / 都道府県 / 市区町村 / 公開状態での絞り込み）
- スポット単位でスタッフ / クーポン / メディア / サービス / 最寄り駅を管理
- 最寄り駅はスポットごとに「徒歩何分まで表示するか」を管理画面で設定でき、既定値は `30` 分
- メディア管理は画像・動画タブに分離
- 画像はドラッグ&ドロップまたはファイル選択で登録可能。新規追加時は複数アップロード、編集時は1枚差し替えに対応（最大 10 件、URL / ストレージパス手入力にも対応）
- スポットカードの画像は、`spots.thumbnail_path` を優先し、未設定時は登録済みメディア画像の `thumbnail_path` または `path` を利用
- 動画は YouTube 埋め込みタグを登録可能（最大 5 件、保存時は埋め込み URL を抽出して保存）
- `php artisan test` でテストを実行
- Docker で `nginx`, `php`, `node`, `mysql`, `redis`, `mailpit`, `phpmyadmin` を起動

## クイックスタート

### Docker を使う場合

1. 環境ファイルを作成します。

```bash
cp .env.example .env
```

2. 基本サービスを起動します。

```bash
docker compose up -d --build nginx php mysql redis mailpit phpmyadmin
```

3. PHP コンテナ内で初期化します。

```bash
docker exec php-plus1-community composer install
docker exec php-plus1-community php artisan key:generate
docker exec php-plus1-community php artisan migrate
```

4. フロントエンドを開発モードで起動します。

```bash
docker compose up node
```

確認先:

- App: `http://localhost:8021`
- Vite: `http://localhost:5185`
- Mailpit: `http://localhost:8035`
- phpMyAdmin: `http://localhost:8022`

### ローカル実行の場合

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
npm run dev
```

ローカル実行時は `.env` の DB / Redis / Mail 設定を自分の環境向けに調整してください。  
Docker 前提の初期値は `.env.example` に入っています。

## よく使うコマンド

```bash
composer run dev
php artisan test
```

`c.sh` には Laravel のキャッシュクリア用コマンドをまとめています。

### 千葉県の鉄道データを元プロジェクトから取り込む

`iplusone_wordpress_laravel_admin` のバックアップ SQL から、千葉県など都道府県単位で駅・路線データを取り込めます。

```bash
php artisan railway:import-source-backups /home/ishii/projects/iplusone_wordpress_laravel_admin/storage/app/backups --pref-code=12
```

件数だけ確認したい場合:

```bash
php artisan railway:import-source-backups /home/ishii/projects/iplusone_wordpress_laravel_admin/storage/app/backups --pref-code=12 --dry-run
```

### 自治体マスタを元CSVから取り込む

JIS市区町村CSVを `cities` / `municipalities` に取り込めます。

```bash
php artisan cities:import-jis-csv /home/ishii/projects/iplusone_wordpress_laravel_admin/storage/app/jis_cities_utf8.csv
```

件数確認のみ:

```bash
php artisan cities:import-jis-csv /home/ishii/projects/iplusone_wordpress_laravel_admin/storage/app/jis_cities_utf8.csv --dry-run
```

自治体財政CSVを `municipalities` / `muni_finance_stats` に取り込む場合:

```bash
php artisan mic:import-fiscal storage/app/muni_finance.csv --year=2024
```

### ローカルに本番相当の全国自治体スポットを入れる

`iplusone_wordpress_laravel_admin` から全国自治体CSVを出力して、ローカル `plus1_community` の Docker 環境へ取り込む手順です。

1. 元プロジェクトで CSV を出力します。

```bash
cd /home/ishii/projects/iplusone_wordpress_laravel_admin
docker compose run --rm --no-deps laravel.app php artisan cities:export-spots-csv storage/app/exports/city-spots.csv --only-complete
```

2. `plus1_community` 側へ CSV をコピーします。

```bash
cd /home/ishii/projects/plus1_community
mkdir -p storage/app/source_imports/exports
cp /home/ishii/projects/iplusone_wordpress_laravel_admin/storage/app/exports/city-spots.csv storage/app/source_imports/exports/
```

3. ローカル Docker の Laravel へ取り込みます。

```bash
cd /home/ishii/projects/plus1_community
docker compose exec -T php php artisan spots:import-city-csv storage/app/source_imports/exports/city-spots.csv --company=全国自治体
```

取り込み結果の目安:

- `created: 1892`
- `wordpress_sites: 1892`
- `skipped: 0`

確認の目安:

- `spots` 総件数がおおむね `1896` 件になる
- `municipal-office-%` の自治体スポットが `1892` 件になる
- 自治体スポットの都道府県数が `47` になる
- ローカル `http://localhost:8021/` と `http://localhost:8021/spots` で自治体スポットが見える

## ドキュメント

- 要件定義: `docs/portal-requirements.md`
- 実装方針: `docs/implementation-outline.md`
- データモデル案: `docs/data-model-draft.md`
- 鉄道データ連携: `docs/railway-integration.md`
- VPS デプロイ手順: `docs/vps-apache-deployment.md`
