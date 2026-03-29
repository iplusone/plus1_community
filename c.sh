php artisan config:clear       # 設定キャッシュのクリア
php artisan cache:clear        # アプリケーションキャッシュのクリア
php artisan route:clear        # ルートキャッシュのクリア
php artisan view:clear         # Bladeビューのキャッシュクリア
php artisan clear-compiled     # compiled.phpの削除（不要な場合も多い）

php artisan config:cache       # 設定ファイルを再キャッシュ
php artisan route:cache        # ルーティングを再キャッシュ
php artisan view:cache         # Bladeを事前コンパイル

composer dump-autoload         # クラスマップ再生成
composer clear-cache           # Composerキャッシュ削除
