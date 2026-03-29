# Plus1 Community

開発用ひな形をコピーした直後でも、そのまま立ち上げて作業を始められるように整えた Laravel 12 ベースのスタータープロジェクトです。

## 技術スタック

- PHP 8.2+
- Laravel 12
- Vite 7
- Tailwind CSS 4
- PHPUnit 11
- Docker Compose

## できること

- `/` で Laravel の welcome ページを表示
- `php artisan test` で最小テストを実行
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
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate
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

## 整備内容

- Docker Compose の構文エラーを修正
- `.env.example` を Docker でそのまま使える値に調整
- `.gitignore`, `.editorconfig`, `.gitattributes` をスターター向けに再整備
- `composer.json` のプロジェクトメタデータを Laravel 初期値から更新
