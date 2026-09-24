#!/usr/bin/env bash

set -euo pipefail

cd /var/www/html

if [ ! -f .env ]; then
    echo '==> creating .env from .env.example'
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    echo '==> installing composer dependencies'
    composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=.\+' .env; then
    echo '==> generating application key'
    php artisan key:generate --force
fi

if [ ! -d node_modules/vite-plus ] || [ package-lock.json -nt node_modules/.package-lock.json ]; then
    echo '==> installing npm dependencies'
    npm ci
fi

echo '==> running migrations'
php artisan migrate --force

echo '==> ready'
