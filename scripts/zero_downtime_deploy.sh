#!/bin/bash
set -e

echo "🚀 Starting Zero-Downtime Deployment..."

# Configuration
REPO_TOKEN=$1
REPO_URL="https://x-access-token:${REPO_TOKEN}@github.com/fast-order-eg/fast-order.git"
BASE_DIR="/home/fast-order-eg.tech/deploy"
RELEASES_DIR="$BASE_DIR/releases"
SHARED_DIR="$BASE_DIR/shared"
CURRENT_DIR="$BASE_DIR/current"
RELEASE_NAME=$(date +"%Y%m%d%H%M%S")
RELEASE_DIR="$RELEASES_DIR/$RELEASE_NAME"

echo "📂 Creating new release directory: $RELEASE_DIR"
mkdir -p "$RELEASE_DIR"

echo "📥 Cloning repository..."
git clone --depth 1 "$REPO_URL" "$RELEASE_DIR"

echo "🔗 Linking shared files..."
# Link .env
if [ -f "$SHARED_DIR/.env" ]; then
    ln -nfs "$SHARED_DIR/.env" "$RELEASE_DIR/.env"
else
    echo "⚠️ WARNING: .env not found in shared directory. Copying from repository."
    cp "$RELEASE_DIR/.env.example" "$SHARED_DIR/.env"
    ln -nfs "$SHARED_DIR/.env" "$RELEASE_DIR/.env"
fi

# Link storage
rm -rf "$RELEASE_DIR/storage"
ln -nfs "$SHARED_DIR/storage" "$RELEASE_DIR/storage"

echo "📦 Installing Composer dependencies..."
cd "$RELEASE_DIR"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "✨ Building Frontend Assets..."
npm install
npm run build

# 🛡️ Zero-Downtime Protection 1: Verify build artifacts
if [ ! -f "$RELEASE_DIR/public/build/manifest.json" ]; then
    echo "❌ ERROR: manifest.json is missing in build output! Aborting deployment to protect live site."
    rm -rf "$RELEASE_DIR"
    exit 1
fi

# 🛡️ Zero-Downtime Protection 2: Preserve old assets so existing active browser tabs do not get 404
if [ -d "$CURRENT_DIR/public/build/assets" ]; then
    echo "🛡️ Preserving existing assets for cached user sessions..."
    cp -n "$CURRENT_DIR/public/build/assets/"* "$RELEASE_DIR/public/build/assets/" 2>/dev/null || true
fi

echo "🗄️ Running database migrations..."
php artisan migrate --force

echo "🔗 Linking public storage..."
php artisan storage:link || true

echo "🧹 Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 🛡️ Zero-Downtime Protection 3: Fix permissions BEFORE swapping symlink (prevents 403 / 404 / white screen)
echo "🔐 Fixing file permissions before activation..."
chown -R fasto5299:nobody "$RELEASE_DIR"
chmod -R 755 "$RELEASE_DIR"
chmod -R 775 "$RELEASE_DIR/bootstrap/cache" 2>/dev/null || true

# 🛡️ Zero-Downtime Protection 4: Atomic symlink swap using mv -Tf
echo "🔄 Swapping symlink atomically for Zero-Downtime..."
ln -sfn "$RELEASE_DIR" "$BASE_DIR/current_tmp"
mv -Tf "$BASE_DIR/current_tmp" "$CURRENT_DIR"

echo "🔄 Restarting Queue Workers..."
php artisan queue:restart || true

echo "🔄 Reloading PHP-FPM / OpenLiteSpeed processes..."
killall -9 lsphp 2>/dev/null || true

echo "🧹 Cleaning up old releases (keeping last 3)..."
cd "$RELEASES_DIR"
ls -1t | tail -n +4 | xargs -r rm -rf

echo "🎉 Zero-Downtime Deployment completed successfully!"
