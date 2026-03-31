# VPS デプロイ手順書

## 目的

`plus1_community` を既存の VPS 運用環境へ追加し、`spottown.iplusone.co.jp` として公開する。

この手順書は、実際に 2026年4月1日 に反映できた手順をそのまま残した runbook である。

## 実環境

- OS: AlmaLinux 9.5
- Apache: 2.4.62
- PHP-FPM: 8.2.28
- MySQL: 8.4.5
- 既存 Apache 設定配置先: `/etc/httpd/conf.d`
- PHP-FPM ソケット: `/run/php-fpm/www.sock`
- 配置先: `/var/www/source/plus1_community`
- 公開ドメイン: `spottown.iplusone.co.jp`
- リポジトリ: `git@github.com:iplusone/plus1_community.git`

## 前提

この VPS にはすでに以下が入っていて、他の Laravel プロジェクトも稼働している。

- `httpd`
- `php-fpm`
- `mysql`
- `composer`
- `node`
- `certbot`

今回の導入は、サーバー新規構築ではなく、既存運用環境への Laravel アプリ追加である。

## 1. コード配置

```bash
cd /var/www/source
git clone git@github.com:iplusone/plus1_community.git
cd plus1_community
```

確認:

```bash
pwd
ls
```

## 2. 依存関係インストール

```bash
cd /var/www/source/plus1_community
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

ビルド完了の目安:

- `public/build/manifest.json` が生成される

## 3. `.env` 作成

```bash
cd /var/www/source/plus1_community
cp .env.example .env
php artisan key:generate
```

今回の本番値:

```env
APP_ENV=production
APP_URL=https://spottown.iplusone.co.jp

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plus1_community
DB_USERNAME=plus1_community
DB_PASSWORD='本番パスワード'
```

補足:

- 初回の動作確認までは `APP_DEBUG=true` のままで進めた
- 公開前には `APP_DEBUG=false` に戻す

確認コマンド:

```bash
grep -E '^(APP_ENV|APP_DEBUG|APP_URL|DB_CONNECTION|DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)=' .env
```

## 4. MySQL 作成

```bash
mysql -u root -p
```

```sql
CREATE DATABASE plus1_community CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'plus1_community'@'localhost' IDENTIFIED BY '本番パスワード';
GRANT ALL PRIVILEGES ON plus1_community.* TO 'plus1_community'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 5. Laravel 初期化

```bash
cd /var/www/source/plus1_community
php artisan migrate --force
php artisan storage:link
sh c.sh
sudo chown -R apache:apache storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

`c.sh` では次をまとめて実行している。

- `config:clear`
- `cache:clear`
- `route:clear`
- `view:clear`
- `clear-compiled`
- `config:cache`
- `route:cache`
- `view:cache`
- `composer dump-autoload`

## 6. Apache 設定

### 6-1. HTTP

`/etc/httpd/conf.d/spottown.conf`

```apache
<VirtualHost *:80>
    ServerName spottown.iplusone.co.jp
    DocumentRoot /var/www/source/plus1_community/public

    <Directory "/var/www/source/plus1_community/public">
        AllowOverride All
        Require all granted
        Options FollowSymLinks
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php-fpm/www.sock|fcgi://localhost/"
    </FilesMatch>

    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [R=301,L]

    ErrorLog /var/log/httpd/spottown-error.log
    CustomLog /var/log/httpd/spottown-access.log combined
</VirtualHost>
```

作成:

```bash
sudo tee /etc/httpd/conf.d/spottown.conf > /dev/null <<'EOF'
<VirtualHost *:80>
    ServerName spottown.iplusone.co.jp
    DocumentRoot /var/www/source/plus1_community/public

    <Directory "/var/www/source/plus1_community/public">
        AllowOverride All
        Require all granted
        Options FollowSymLinks
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php-fpm/www.sock|fcgi://localhost/"
    </FilesMatch>

    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [R=301,L]

    ErrorLog /var/log/httpd/spottown-error.log
    CustomLog /var/log/httpd/spottown-access.log combined
</VirtualHost>
EOF
```

確認:

```bash
sudo apachectl configtest
sudo systemctl reload httpd
curl -I http://spottown.iplusone.co.jp
```

期待値:

- `Syntax OK`
- `HTTP/1.1 301 Moved Permanently`

### 6-2. HTTPS

```bash
sudo certbot --apache -d spottown.iplusone.co.jp
```

証明書発行後、`certbot` が `/etc/httpd/conf.d/spottown-le-ssl.conf` を自動生成する。

確認:

```bash
sudo apachectl configtest
curl -I https://spottown.iplusone.co.jp
```

期待値:

- `Syntax OK`
- `HTTP/1.1 200 OK`

## 7. 初期表示確認

確認 URL:

- `https://spottown.iplusone.co.jp/`
- `https://spottown.iplusone.co.jp/spots`

初回はデータ未投入のため、次の状態で正常。

- 総拠点数 `0`
- おすすめ `0`
- 最新 `0`
- ランダム `0`

## 8. 自治体 CSV の取り込み

元プロジェクト側で CSV を出力:

```bash
cd /var/www/source/iplusone_wordpress_laravel_admin
php artisan cities:export-spots-csv storage/app/exports/city-spots.csv --only-complete
```

出力例:

- 書き出し件数: `1892`
- 必須項目が揃った件数: `1892`
- スキップ件数: `0`

`plus1_community` 側で取り込み:

```bash
cd /var/www/source/plus1_community
composer install --no-dev --optimize-autoloader
php artisan spots:import-city-csv /var/www/source/iplusone_wordpress_laravel_admin/storage/app/exports/city-spots.csv
```

取り込み結果の例:

- `company_id: 1`
- `created: 1892`
- `updated: 0`
- `wordpress_sites: 1892`
- `skipped: 0`

## 9. 反映後の確認

確認 URL:

- `https://spottown.iplusone.co.jp/`
- `https://spottown.iplusone.co.jp/spots`

確認ポイント:

- 総拠点数が増えている
- 一覧に自治体スポットが表示される
- 詳細ページが開く
- `公式サイト` リンクが表示される

必要なら再キャッシュ:

```bash
cd /var/www/source/plus1_community
sh c.sh
```

## 10. 追加デプロイ

通常更新:

```bash
cd /var/www/source/plus1_community
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build
sh c.sh
sudo systemctl reload httpd
```

マイグレーションがある場合:

```bash
cd /var/www/source/plus1_community
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
sh c.sh
sudo systemctl reload httpd
```

## 11. 公開前チェック

- `.env` の `APP_DEBUG` を `false` にする
- `APP_URL=https://spottown.iplusone.co.jp` になっている
- `storage` と `bootstrap/cache` の権限が維持されている
- `curl -I https://spottown.iplusone.co.jp` が `200` を返す
- ブラウザでトップ、一覧、詳細を確認する
