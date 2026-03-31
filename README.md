# Plus1 Community

拠点(スポット)ベースのポータルプラットフォームです。企業傘下の店舗・支社・営業所などの拠点が独立したページを持ち、情報発信・検索・集客を実現します。

## 技術スタック

- PHP 8.2+
- Laravel 12
- Vite 7
- Tailwind CSS 4
- PHPUnit 11
- Docker Compose

## できること

- `/` でトップページを表示（おすすめ・最新・ランダム各 10 件）
- `/spots` でスポット検索・一覧（キーワード / エリア / ジャンル / タグ / ソート / カード・リスト切替）
- `/spots/{slug}` でスポット詳細（基本情報・営業時間・サービス・メニュー・メディア・スタッフ・クーポン・最寄り駅）
- `/admin/spots` で管理画面（スポット CRUD・階層管理・スタッフ / クーポン / メディア / サービス / 最寄り駅管理）
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

## ドキュメント

- 要件定義: `docs/portal-requirements.md`
- 実装方針: `docs/implementation-outline.md`
- データモデル案: `docs/data-model-draft.md`
- 鉄道データ連携: `docs/railway-integration.md`
