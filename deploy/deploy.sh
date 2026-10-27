#!/usr/bin/env bash
# =============================================================================
#  Matjar — one-shot server deployment (Ubuntu / Debian + nginx + PHP-FPM + MySQL)
#
#  Run as root on the server that answers for the domain:
#     sudo DOMAIN=shop.syseagles.com ADMIN_EMAIL=you@syseagles.com bash deploy/deploy.sh
#
#  What it does (idempotent, safe to re-run):
#    1. installs PHP-FPM + extensions, git, certbot (and MariaDB if no MySQL exists)
#    2. creates ONE MySQL user (default: dnf-shops) that creates and uses
#         master                 -> master database (shops, plans, platform admins)
#         <shop-address>         -> one secondary database per shop, named after the shop
#                                   (e.g. shop "eagle-electronics" -> database `eagle-electronics`)
#    3. clones / updates the code into /var/www/<DOMAIN>
#    4. runs the installer (multi-shop SaaS by default) without the web wizard
#    5. adds a dedicated PHP-FPM pool + nginx site for DOMAIN (other sites untouched)
#    6. gets a Let's Encrypt certificate
#    7. installs the cron job (stock sync + courier tracking, every minute)
#  Credentials are written to /root/matjar-credentials.txt (chmod 600).
# =============================================================================
set -euo pipefail

DOMAIN="${DOMAIN:-shop.syseagles.com}"
APP_DIR="${APP_DIR:-/var/www/${DOMAIN}}"
REPO_URL="${REPO_URL:-https://github.com/mrdnf/Shop.git}"
BRANCH="${BRANCH:-claude/determined-davinci-c2j928}"
MODE="${MODE:-multi}"                          # multi (SaaS: master + one DB per shop) | single
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-dnf-shops}"
DB_PASS="${DB_PASS:-}"                         # generated when empty
MASTER_DB="${MASTER_DB:-master}"               # master (platform) database
SHOP_DB_PREFIX="${SHOP_DB_PREFIX:-}"          # empty = each shop DB is named exactly like the shop
MYSQL_ROOT_USER="${MYSQL_ROOT_USER:-root}"     # admin account used once to create DB_USER
MYSQL_ROOT_PASS="${MYSQL_ROOT_PASS:-}"         # empty = unix-socket auth (sudo mysql)
COMPANY="${COMPANY:-SysEagles Shop}"
COMPANY_AR="${COMPANY_AR:-متجر سيس إيجلز}"
ADMIN_NAME="${ADMIN_NAME:-Administrator}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@${DOMAIN#*.}}"
ADMIN_PASS="${ADMIN_PASS:-}"                   # generated when empty
LOCALE="${LOCALE:-en}"
PRIMARY="${PRIMARY:-#7c3aed}"
CURRENCY="${CURRENCY:-USD}"
CURRENCY_SYMBOL="${CURRENCY_SYMBOL:-\$}"
DEMO="${DEMO:-1}"                              # 1 = create a demo shop with sample products
LE_EMAIL="${LE_EMAIL:-$ADMIN_EMAIL}"
SKIP_SSL="${SKIP_SSL:-0}"
WEB_USER="${WEB_USER:-www-data}"
CRED_FILE="${CRED_FILE:-/root/matjar-credentials.txt}"

log()  { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!! %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mxx %s\033[0m\n' "$*" >&2; exit 1; }
rand() { php -r 'echo substr(preg_replace("/[^A-Za-z0-9]/", "", base64_encode(random_bytes(96))), 0, (int) $argv[1]);' "${1:-24}"; }
svc()  { if command -v systemctl >/dev/null && [ -d /run/systemd/system ]; then systemctl "$1" "$2"; else service "$2" "$1"; fi; }

[ "$(id -u)" -eq 0 ] || die "Run as root (sudo)."
[[ "$MASTER_DB" =~ ^[A-Za-z0-9_-]+$ ]]      || die "MASTER_DB may only contain letters, numbers, _ and -"
[[ "$SHOP_DB_PREFIX" =~ ^[A-Za-z0-9_-]*$ ]] || die "SHOP_DB_PREFIX may only contain letters, numbers, _ and -"
[[ "$DB_USER" =~ ^[A-Za-z0-9_-]{1,32}$ ]]   || die "DB_USER may only contain letters, numbers, _ and - (max 32)"

# ----------------------------------------------------------------------------- 1. packages
log "Installing packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq git curl ca-certificates cron logrotate nginx >/dev/null
if ! command -v php >/dev/null || ! php -m | grep -qi pdo_mysql; then
    apt-get install -y -qq php-fpm php-cli php-mysql php-curl php-mbstring php-xml php-gd php-zip php-intl >/dev/null
fi
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
php -r 'exit(version_compare(PHP_VERSION, "8.1.0", ">=") ? 0 : 1);' || die "PHP >= 8.1 required (found $PHP_VER)."
apt-get install -y -qq "php${PHP_VER}-fpm" "php${PHP_VER}-mysql" "php${PHP_VER}-curl" "php${PHP_VER}-mbstring" \
    "php${PHP_VER}-xml" "php${PHP_VER}-gd" "php${PHP_VER}-zip" "php${PHP_VER}-intl" >/dev/null
for ext in pdo_mysql sodium curl mbstring fileinfo; do php -m | grep -qi "^$ext$" || die "PHP extension $ext missing"; done

if [ "$DB_HOST" = "127.0.0.1" ] || [ "$DB_HOST" = "localhost" ]; then
    if ! command -v mysql >/dev/null; then
        log "No MySQL found — installing MariaDB"
        apt-get install -y -qq mariadb-server >/dev/null
        svc enable mariadb || true
        svc start mariadb
    fi
fi
[ "$SKIP_SSL" = "1" ] || apt-get install -y -qq certbot python3-certbot-nginx >/dev/null

# ----------------------------------------------------------------------------- 2. one DB user
log "Creating MySQL user '${DB_USER}'"
ROOT_ARGS=(-u "$MYSQL_ROOT_USER" -h "$DB_HOST" -P "$DB_PORT")
[ "$DB_HOST" = "127.0.0.1" ] && [ -z "$MYSQL_ROOT_PASS" ] && ROOT_ARGS=(-u "$MYSQL_ROOT_USER")   # socket auth
[ -n "$MYSQL_ROOT_PASS" ] && export MYSQL_PWD="$MYSQL_ROOT_PASS"
mysql "${ROOT_ARGS[@]}" -e "SELECT 1" >/dev/null || die "Cannot connect to MySQL as ${MYSQL_ROOT_USER}. Set MYSQL_ROOT_PASS."

if [ -f "$APP_DIR/config/config.php" ]; then
    INSTALLED=1
else
    INSTALLED=0
    [ -n "$DB_PASS" ] || DB_PASS="$(rand 28)"
    # Which databases the single user may create/use.
    if [ -n "$SHOP_DB_PREFIX" ]; then
        # Shops share a prefix: restrict the user to master + prefix* only.
        GRANT_TARGETS=("\`${MASTER_DB//_/\\_}\`" "\`${SHOP_DB_PREFIX//_/\\_}%\`")
    else
        # Shop DBs are named after each shop, so names are unknown in advance:
        # the user needs rights on any database name. The app refuses system DB
        # names and never reuses an existing database.
        GRANT_TARGETS=("\`%\`")
        warn "DB user '${DB_USER}' gets rights on ALL databases (needed to name shop DBs after shops)."
        warn "If this MySQL server also hosts other sites, set SHOP_DB_PREFIX (e.g. shop_) to restrict it."
    fi
    DB_ACCOUNT_HOST="localhost"; [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ] && DB_ACCOUNT_HOST="%"
    for H in "$DB_ACCOUNT_HOST" 127.0.0.1; do
        mysql "${ROOT_ARGS[@]}" <<SQL
CREATE USER IF NOT EXISTS '${DB_USER}'@'${H}' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'${H}' IDENTIFIED BY '${DB_PASS}';
$(for T in "${GRANT_TARGETS[@]}"; do echo "GRANT ALL PRIVILEGES ON ${T}.* TO '${DB_USER}'@'${H}';"; done)
FLUSH PRIVILEGES;
SQL
    done
fi
unset MYSQL_PWD

# ----------------------------------------------------------------------------- 3. code
log "Deploying code to ${APP_DIR} (${BRANCH})"
if [ -d "$APP_DIR/.git" ]; then
    git -C "$APP_DIR" fetch --quiet origin "$BRANCH"
    git -C "$APP_DIR" checkout --quiet -B "$BRANCH" "origin/$BRANCH"
    git -C "$APP_DIR" reset --quiet --hard "origin/$BRANCH"
else
    [ -e "$APP_DIR" ] && [ -n "$(ls -A "$APP_DIR" 2>/dev/null)" ] && die "$APP_DIR exists and is not a git checkout."
    git clone --quiet --branch "$BRANCH" "$REPO_URL" "$APP_DIR" \
        || die "git clone failed. For a private repo use REPO_URL=git@github.com:mrdnf/Shop.git with a deploy key, or https://<token>@github.com/mrdnf/Shop.git"
fi
mkdir -p "$APP_DIR"/{config,storage/logs,storage/cache,public/uploads}
chown -R root:root "$APP_DIR"
chown -R "$WEB_USER":"$WEB_USER" "$APP_DIR/config" "$APP_DIR/storage" "$APP_DIR/public/uploads"
chmod 750 "$APP_DIR/config"

# ----------------------------------------------------------------------------- 4. install
if [ "$INSTALLED" = "0" ]; then
    log "Installing Matjar (${MODE} mode)"
    [ -n "$ADMIN_PASS" ] || ADMIN_PASS="$(rand 16)"
    DEMO_FLAG=(); [ "$DEMO" = "1" ] && DEMO_FLAG=(--demo)
    sudo -u "$WEB_USER" env MATJAR_DB_PASS="$DB_PASS" MATJAR_ADMIN_PASSWORD="$ADMIN_PASS" \
        php "$APP_DIR/bin/install.php" --mode="$MODE" \
        --db-host="$DB_HOST" --db-port="$DB_PORT" --db-user="$DB_USER" \
        --platform-db="$MASTER_DB" --tenant-prefix="$SHOP_DB_PREFIX" \
        --company="$COMPANY" --company-ar="$COMPANY_AR" --admin-name="$ADMIN_NAME" \
        --admin-email="$ADMIN_EMAIL" --locale="$LOCALE" --primary="$PRIMARY" \
        --currency="$CURRENCY" --currency-symbol="$CURRENCY_SYMBOL" "${DEMO_FLAG[@]}"
    chmod 640 "$APP_DIR/config/config.php"
    umask 077
    cat > "$CRED_FILE" <<EOF
Matjar on https://${DOMAIN}   (installed $(date -u +%F))
Mode            : ${MODE}
Admin login     : https://${DOMAIN}$([ "$MODE" = multi ] && echo /platform/login || echo /login)
Admin email     : ${ADMIN_EMAIL}
Admin password  : ${ADMIN_PASS}
MySQL user      : ${DB_USER}
MySQL password  : ${DB_PASS}
Master database : ${MASTER_DB}
Shop databases  : ${SHOP_DB_PREFIX}<shop-address>  (e.g. ${SHOP_DB_PREFIX}demo)
EOF
    umask 022
else
    log "Already installed — running migrations on master + every shop database"
    sudo -u "$WEB_USER" php "$APP_DIR/bin/migrate.php"
fi

# ----------------------------------------------------------------------------- 5. php-fpm + nginx
log "Configuring PHP-FPM pool and nginx site"
FPM_SOCK="/run/php/matjar-${DOMAIN}.sock"
cat > "/etc/php/${PHP_VER}/fpm/pool.d/matjar-${DOMAIN}.conf" <<EOF
[matjar-${DOMAIN}]
user = ${WEB_USER}
group = ${WEB_USER}
listen = ${FPM_SOCK}
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 20
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 6
php_admin_value[upload_max_filesize] = 8M
php_admin_value[post_max_size] = 12M
php_admin_value[memory_limit] = 256M
php_admin_value[expose_php] = Off
php_admin_value[session.cookie_httponly] = 1
php_admin_value[error_log] = ${APP_DIR}/storage/logs/php-error.log
EOF
php-fpm"${PHP_VER}" -t >/dev/null 2>&1 || die "php-fpm config test failed"
svc restart "php${PHP_VER}-fpm"

NGINX_SITE="/etc/nginx/sites-available/${DOMAIN}"
LISTEN6=""; [ -s /proc/net/if_inet6 ] && LISTEN6="listen [::]:80;"
OTHER_CONF="$(grep -rlF "$DOMAIN" /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null | grep -v "/${DOMAIN}$" || true)"
if [ -n "$OTHER_CONF" ]; then
    warn "Another nginx config already mentions ${DOMAIN}:"
    echo "$OTHER_CONF"
    warn "Remove its ${DOMAIN} server block and re-run, otherwise nginx may keep serving the old site."
fi
if [ -f "$NGINX_SITE" ] && ! grep -q "managed-by-matjar" "$NGINX_SITE"; then
    cp "$NGINX_SITE" "${NGINX_SITE}.bak.$(date +%s)"
    warn "Existing ${NGINX_SITE} backed up and replaced."
fi
# Keep the file once certbot has added SSL to it.
if [ ! -f "$NGINX_SITE" ] || ! grep -q "managed-by-matjar" "$NGINX_SITE" || ! grep -q "ssl_certificate" "$NGINX_SITE"; then
cat > "$NGINX_SITE" <<EOF
# managed-by-matjar
server {
    listen 80;
    ${LISTEN6}
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    client_max_body_size 12M;
    charset utf-8;

    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    access_log /var/log/nginx/${DOMAIN}.access.log;
    error_log  /var/log/nginx/${DOMAIN}.error.log;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }

    location ~* ^/(assets|uploads)/.+\.(css|js|png|jpe?g|webp|gif|svg|woff2?)$ {
        expires 30d; access_log off; add_header Cache-Control "public"; try_files \$uri =404;
    }
    location ~ ^/uploads/.*\.(php|phtml|phar)$ { deny all; }
    location ~ /\.(?!well-known) { deny all; }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param HTTPS \$https if_not_empty;
        fastcgi_pass unix:${FPM_SOCK};
        fastcgi_read_timeout 120;
    }
    location ~ \.php$ { return 404; }
}
EOF
fi
ln -sf "$NGINX_SITE" "/etc/nginx/sites-enabled/${DOMAIN}"
nginx -t || die "nginx config test failed (nothing reloaded)."
if pgrep -x nginx >/dev/null; then svc reload nginx; else svc start nginx; fi

# ----------------------------------------------------------------------------- 6. SSL
if [ "$SKIP_SSL" != "1" ]; then
    log "Requesting Let's Encrypt certificate for ${DOMAIN}"
    if certbot --nginx -n --agree-tos --redirect -m "$LE_EMAIL" -d "$DOMAIN"; then
        svc reload nginx
    else
        warn "certbot failed — check that ${DOMAIN} points to this server and port 80 is open. Re-run later: certbot --nginx -d ${DOMAIN}"
    fi
fi

# ----------------------------------------------------------------------------- 7. cron
log "Installing cron job (stock sync + courier tracking every minute)"
cat > "/etc/cron.d/matjar-${DOMAIN//./-}" <<EOF
# Matjar scheduler for ${DOMAIN}: pulls inventory balances (per-shop interval) and courier tracking events.
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
* * * * * ${WEB_USER} php ${APP_DIR}/bin/cron.php >> ${APP_DIR}/storage/logs/cron.log 2>&1
EOF
chmod 644 "/etc/cron.d/matjar-${DOMAIN//./-}"
cat > "/etc/logrotate.d/matjar-${DOMAIN//./-}" <<EOF
${APP_DIR}/storage/logs/*.log {
    weekly
    rotate 8
    compress
    missingok
    notifempty
    su ${WEB_USER} ${WEB_USER}
    create 0640 ${WEB_USER} ${WEB_USER}
}
EOF
svc enable cron 2>/dev/null || true
svc start cron 2>/dev/null || true
sudo -u "$WEB_USER" php "$APP_DIR/bin/cron.php" >/dev/null && echo "cron run OK"

# ----------------------------------------------------------------------------- done
log "Done"
SCHEME=https; [ "$SKIP_SSL" = "1" ] && SCHEME=http
echo "  Website      : ${SCHEME}://${DOMAIN}/"
[ "$MODE" = "multi" ] && echo "  Platform     : ${SCHEME}://${DOMAIN}/platform/login" && echo "  Demo shop    : ${SCHEME}://${DOMAIN}/s/demo"
[ -f "$CRED_FILE" ] && echo "  Credentials  : ${CRED_FILE}  (cat it, then store it somewhere safe)"
echo "  Cron         : /etc/cron.d/matjar-${DOMAIN//./-}  -> ${APP_DIR}/storage/logs/cron.log"
