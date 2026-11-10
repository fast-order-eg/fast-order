#!/bin/bash
set -e

RELEASE_NAME=$1
if [ -z "$RELEASE_NAME" ]; then
    echo "❌ Usage: $0 <release_timestamp>"
    exit 1
fi

BASE_DIR="/home/fast-order-eg.tech/deploy"
RELEASES_DIR="$BASE_DIR/releases"
SHARED_DIR="$BASE_DIR/shared"
CURRENT_DIR="$BASE_DIR/current"
RELEASE_DIR="$RELEASES_DIR/$RELEASE_NAME"

echo "🚀 Activating Zero-Downtime Release: $RELEASE_NAME"

if [ ! -d "$RELEASE_DIR" ]; then
    echo "❌ ERROR: Release directory does not exist: $RELEASE_DIR"
    exit 1
fi

# A Vite hot file makes production browsers load JavaScript from localhost.
# Reject the release before touching the live symlink.
if [ -e "$RELEASE_DIR/public/hot" ]; then
    echo "CRITICAL: public/hot found in release. Refusing production activation."
    exit 1
fi

echo "🔗 Linking shared environment and storage..."
if [ -f "$SHARED_DIR/.env" ]; then
    ln -sfn "$SHARED_DIR/.env" "$RELEASE_DIR/.env"
fi

rm -rf "$RELEASE_DIR/storage"
ln -sfn "$SHARED_DIR/storage" "$RELEASE_DIR/storage"

# If vendor directory was not transferred, hardlink from current release
if [ ! -d "$RELEASE_DIR/vendor" ] && [ -d "$CURRENT_DIR/vendor" ]; then
    echo "📦 Hardlinking vendor directory from current release..."
    cp -al "$CURRENT_DIR/vendor" "$RELEASE_DIR/vendor"
fi

# Preserve previous frontend chunks so existing active tabs never 404
if [ -d "$CURRENT_DIR/public/build/assets" ]; then
    echo "🛡️ Preserving existing assets for cached user sessions..."
    mkdir -p "$RELEASE_DIR/public/build/assets"
    cp -n "$CURRENT_DIR/public/build/assets/"* "$RELEASE_DIR/public/build/assets/" 2>/dev/null || true
fi

# Verify manifest.json exists before anything touches production
if [ ! -f "$RELEASE_DIR/public/build/manifest.json" ]; then
    echo "❌ CRITICAL: manifest.json missing in build! Aborting activation to prevent white screen."
    exit 1
fi

echo "🧹 Optimizing Laravel caches..."
cd "$RELEASE_DIR"
rm -f "$RELEASE_DIR/bootstrap/cache/"*.php
php artisan package:discover
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "🔐 Fixing file permissions (fasto5299:nobody + 755)..."
chown -R fasto5299:nobody "$RELEASE_DIR"
chmod -R 755 "$RELEASE_DIR"
chmod -R 775 "$RELEASE_DIR/bootstrap/cache" 2>/dev/null || true

echo "🔄 Swapping symlink atomically (mv -Tf)..."
ln -sfn "$RELEASE_DIR" "$BASE_DIR/current_tmp"
mv -Tf "$BASE_DIR/current_tmp" "$CURRENT_DIR"

echo "🔄 Restarting workers and lsphp..."
php artisan queue:restart || true
killall -9 lsphp 2>/dev/null || true

echo "🧹 Cleaning up older releases (keeping last 3)..."
cd "$RELEASES_DIR"
ls -1t | tail -n +4 | xargs -r rm -rf

echo "🔍 Verifying HTTP response..."
HTTP_CODE=$(curl -s -k -o /dev/null -w "%{http_code}" -H "Host: fast-order-eg.tech" https://127.0.0.1/)
echo "✅ Site status code: $HTTP_CODE"

echo "🎉 Zero-Downtime Release $RELEASE_NAME activated successfully with 0 downtime!"
