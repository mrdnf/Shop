# Deploying to shop.syseagles.com

`shop.syseagles.com` already resolves to the syseagles.com server (169.58.166.164,
nginx). The script below adds a **new** nginx site and PHP-FPM pool for the shop. It
does not touch the existing syseagles.com site.

## 1. Run the deploy script (as root on the server)

```bash
# get the script (or clone the repo first and run deploy/deploy.sh from it)
curl -fsSLO https://raw.githubusercontent.com/mrdnf/Shop/claude/determined-davinci-c2j928/deploy/deploy.sh

sudo DOMAIN=shop.syseagles.com \
     ADMIN_EMAIL=you@syseagles.com \
     bash deploy.sh
```

If the GitHub repo is private, give the server read access first and pass the URL:
`REPO_URL=git@github.com:mrdnf/Shop.git` (with a deploy key) or
`REPO_URL=https://<token>@github.com/mrdnf/Shop.git`.

If MySQL's root login needs a password, also pass `MYSQL_ROOT_PASS=...`. It is used only
once, to create the app's database user.

When it finishes it prints the URLs. The generated passwords are in
`/root/matjar-credentials.txt`.

## 2. What you get

| Item | Value |
|---|---|
| Code | `/var/www/shop.syseagles.com` (branch `claude/determined-davinci-c2j928`) |
| MySQL user (the only one the app uses) | **`dnf-shops`** |
| Master database | **`master`** (shops, plans, platform admins) |
| Shop databases | **named after each shop**: shop `eagle-electronics` → database `` `eagle-electronics` `` |
| Mode | multi-shop SaaS (`MODE=single` for one shop) |
| Platform admin | `https://shop.syseagles.com/platform/login` |
| Shops | `https://shop.syseagles.com/s/<shop>` (+ `/admin`, `/driver`, `/account`) |
| PHP-FPM pool | `/etc/php/<ver>/fpm/pool.d/matjar-shop.syseagles.com.conf` |
| nginx site | `/etc/nginx/sites-available/shop.syseagles.com` |
| SSL | Let's Encrypt via certbot (auto-renew), HTTP → HTTPS redirect |
| **Cron** | `/etc/cron.d/matjar-shop-syseagles-com`, every minute as `www-data` |
| Logs | `storage/logs/{cron,php-error}.log`, rotated weekly |

### The cron job

```
* * * * * www-data php /var/www/shop.syseagles.com/bin/cron.php >> .../storage/logs/cron.log 2>&1
```

Each minute it visits every active shop and:
- runs the **scheduled stock sync** when that shop's interval is due (Admin →
  Inventory sync → "Scheduled sync every N minutes");
- pulls **courier tracking updates** for shipments on API couriers.

A lock file keeps runs from overlapping. Check it with `tail -f .../storage/logs/cron.log`.

### About the single DB user

Shop databases are named after the shop, so their names aren't known in advance.
That means `dnf-shops` is granted rights on **all** databases (`` `%`.* ``) so it can
create them. To limit the risk, the app:
- refuses system names (`mysql`, `sys`, `information_schema`, `performance_schema`,
  `master`, `test`) as shop addresses;
- never takes over a database that already exists.

If this MySQL server also holds other sites' databases (e.g. syseagles.com) and you'd
rather restrict the user, deploy with a prefix:
`SHOP_DB_PREFIX=shop_`. Shop DBs then become `shop_eagle-electronics`, and `dnf-shops`
only gets rights on `master` and `shop_*`.

## 3. Updating later

```bash
sudo bash /var/www/shop.syseagles.com/deploy/update.sh
```
This pulls the branch, migrates `master` and every shop database, and reloads PHP-FPM.
Re-running `deploy.sh` is also safe: it skips the install and keeps your data.

## Optional settings (environment variables for deploy.sh)

`MODE` (multi|single) · `DB_USER` · `DB_PASS` · `MASTER_DB` · `SHOP_DB_PREFIX` ·
`MYSQL_ROOT_USER` / `MYSQL_ROOT_PASS` · `DB_HOST` / `DB_PORT` · `COMPANY` / `COMPANY_AR` ·
`ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASS` · `LOCALE` (en|ar) · `PRIMARY` (#hex) ·
`CURRENCY` / `CURRENCY_SYMBOL` · `DEMO` (1|0) · `LE_EMAIL` · `SKIP_SSL` · `BRANCH` · `REPO_URL`
