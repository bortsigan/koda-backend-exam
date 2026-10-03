#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

echo "Waiting for the database to be ready..."
until php artisan migrate --force > /tmp/migrate.log 2>&1; do
    sleep 2
done
cat /tmp/migrate.log

php artisan db:seed --force

php artisan serve --host=0.0.0.0 --port=8000
