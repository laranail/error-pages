# VPS with git + Nginx

Deploy the static error pages on a VPS so Nginx serves a branded 503 even when PHP-FPM is down.

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

2. Build the static pages and Nginx snippet:

   ```bash
   php artisan server-error-pages:build
   ```

   This writes `public/errors/*.html` and, by default, the snippet to `storage/app/server-error-pages/errors.conf`.

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

   A full reference vhost ships at `resources/server/nginx/vps-vhost.conf.stub`.

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
sudo systemctl start php8.4-fpm
```

You should get the branded static 503 while PHP is stopped — no Blade ran, Nginx served the flat file.

> `fastcgi_intercept_errors on;` is the load-bearing line. Without it, Nginx passes PHP-FPM's raw upstream error straight through and your `error_page` directives never fire.

## Related

- [`server-error-pages:build`](../tools/build-command.md) · [`server-error-pages:server-config`](../tools/server-config-command.md)
- [Zero-downtime static pages](zero-downtime-static-pages.md)

---
[← Docs index](../../README.md#documentation)
