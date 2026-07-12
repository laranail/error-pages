# VPS with git + Nginx

Deploy the static error pages on a VPS so Nginx serves a branded 503 — with its stylesheet and script — even when PHP-FPM is down.

## Prerequisites

- SSH access to the server and a git-based deploy.
- Nginx in front of PHP-FPM.
- `server.profile` set to `vps` (the default).

## Steps

1. Deploy your code and install dependencies:

   ```bash
   git pull
   composer install --no-dev
   ```

2. Build the static pages, linked assets, and Nginx snippet:

   ```bash
   php artisan server-error-pages:build
   ```

   This writes `public/errors/*.html`, copies the linked bundle to `public/vendor/server-error-pages/` (`output.assets_path`), and, by default, writes the snippet to `storage/app/server-error-pages/errors.conf`.

3. Include the generated snippet in your `server { }` block, and make sure the PHP location intercepts FastCGI errors:

   ```nginx
   server {
       listen 80;
       server_name example.com;
       root /var/www/app/public;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           include fastcgi_params;
           fastcgi_pass unix:/run/php/php-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;

           # Serve the static error pages when PHP-FPM is down or overloaded.
           fastcgi_intercept_errors on;
       }

       # Generated branded static pages + security headers.
       include /var/www/app/storage/app/server-error-pages/errors.conf;
   }
   ```

   The linked CSS/JS live under `public/vendor/server-error-pages/` and are served straight from `root` by the `location /` block — Nginx delivers them even while PHP-FPM is down, so the fallback page keeps its styling. Only `/errors/` is marked `internal` (so the raw HTML is not directly browsable); the assets URL is deliberately kept outside it. A full reference vhost ships at `stubs/nginx/vps-vhost.conf.stub`.

4. Reload Nginx:

   ```bash
   sudo nginx -t && sudo systemctl reload nginx
   ```

## Smoke test

Prove the app-down path actually works by stopping PHP and hitting the site:

```bash
sudo systemctl stop php8.4-fpm
curl -sSI http://example.com/ | head -n1     # 503 from the static page
curl -s http://example.com/ | grep -i '<title>'
curl -sSI http://example.com/vendor/server-error-pages/css/error-pages.css | head -n1  # 200
sudo systemctl start php8.4-fpm
```

You should get the branded static 503 while PHP is stopped — no Blade ran, Nginx served the flat file and its stylesheet.

> `fastcgi_intercept_errors on;` is the load-bearing line. Without it, Nginx passes PHP-FPM's raw upstream error straight through and your `error_page` directives never fire.

## Related

- [`server-error-pages:build`](../tools/build-command.md) · [`server-error-pages:server-config`](../tools/server-config-command.md)
- [Zero-downtime static pages](zero-downtime-static-pages.md)

---
[← Docs index](../../README.md#documentation)
