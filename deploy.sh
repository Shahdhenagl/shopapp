#!/usr/bin/env bash
#
# One-command deploy for shopapp.pixelmindeg.com (Hostinger shared / hPanel).
#
# One-time setup on the server (see DEPLOY-GIT.md), then every deploy is:
#     cd ~/shop && git pull && ./deploy.sh
# or just:  ~/shop/deploy.sh   (it pulls itself)
#
# It keeps the current split layout: the Laravel app lives in `laravel/` and its
# public/ is served from the subdomain's `public_html/`. .env, vendor/ and
# storage/ on the server are preserved (never overwritten by a deploy).

set -euo pipefail

REPO="$HOME/shop"
APP="$HOME/domains/shopapp.pixelmindeg.com/laravel"
PUB="$HOME/domains/shopapp.pixelmindeg.com/public_html"

echo ">> [1/5] pulling latest from git…"
cd "$REPO"
git pull --ff-only

echo ">> [2/5] syncing backend code (keeping .env / vendor / storage)…"
if command -v rsync >/dev/null 2>&1; then
  rsync -a --delete \
    --exclude='.env' --exclude='vendor/' --exclude='storage/' \
    --exclude='bootstrap/cache/' --exclude='public/' --exclude='composer.phar' \
    "$REPO/api/" "$APP/"
else
  cp -a "$REPO/api/." "$APP/"
  rm -rf "$APP/public"
fi

echo ">> [3/5] publishing public/ (store + dashboard + entrypoint) to public_html…"
# Drop the built SPAs first so renamed/hashed assets don't pile up.
rm -rf "$PUB/dashboard" "$PUB/store"
cp -a "$REPO/api/public/." "$PUB/"
# Point the public entrypoint at the app one directory up (split layout).
sed -i "s|'/\.\./|'/../laravel/|g" "$PUB/index.php"
ln -sfn "$APP/storage/app/public" "$PUB/storage"

echo ">> [4/5] composer (only if vendor is missing)…"
cd "$APP"
if [ ! -d vendor ]; then
  if [ ! -f composer.phar ]; then
    php -r "copy('https://getcomposer.org/installer','composer-setup.php');"
    php composer-setup.php && rm -f composer-setup.php
  fi
  php composer.phar install --no-dev --optimize-autoloader
fi

echo ">> [5/5] migrate + clear caches…"
php artisan migrate --force
php artisan config:clear
php artisan route:clear
chmod -R 775 storage bootstrap/cache

echo ">> DONE ✅  https://shopapp.pixelmindeg.com"
