#!/usr/bin/env bash
# Pull the latest code and upgrade the master DB + every shop DB.
#   sudo DOMAIN=shop.syseagles.com bash /var/www/shop.syseagles.com/deploy/update.sh
set -euo pipefail
DOMAIN="${DOMAIN:-shop.syseagles.com}"
APP_DIR="${APP_DIR:-/var/www/${DOMAIN}}"
BRANCH="${BRANCH:-$(git -C "$APP_DIR" rev-parse --abbrev-ref HEAD)}"
WEB_USER="${WEB_USER:-www-data}"
[ "$(id -u)" -eq 0 ] || { echo "Run as root"; exit 1; }

git -C "$APP_DIR" fetch --quiet origin "$BRANCH"
git -C "$APP_DIR" reset --quiet --hard "origin/$BRANCH"
chown -R "$WEB_USER":"$WEB_USER" "$APP_DIR/config" "$APP_DIR/storage" "$APP_DIR/public/uploads"
sudo -u "$WEB_USER" php "$APP_DIR/bin/migrate.php"
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
if command -v systemctl >/dev/null && [ -d /run/systemd/system ]; then systemctl reload "php${PHP_VER}-fpm"; else service "php${PHP_VER}-fpm" reload; fi
echo "Updated to $(git -C "$APP_DIR" log -1 --format='%h %s')"
