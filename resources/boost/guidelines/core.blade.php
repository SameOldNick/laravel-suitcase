## Laravel Suitcase

Laravel Suitcase (`sameoldnick/laravel-suitcase`) packages a Laravel application for deployment on
shared hosting (cPanel, DirectAdmin). `php artisan suitcase:pack` builds a deployable ZIP:

- `laravel/` - the application (app, config, routes, `storage/` skeleton, `vendor/`, generated `.env`).
- `public_html/` - the contents of `public/` plus the shared-hosting `index.php` and `constants.php`.
- `INSTALL.txt` - generated deployment steps.
- an optional SQL dump (for example `database.sql`).

Use it only for shared hosting; that environment cannot run asynchronous queues,
broadcasting/WebSockets, or long-running Artisan commands/daemons.

### Setup

```bash
composer require sameoldnick/laravel-suitcase

php artisan vendor:publish --tag=suitcase-config   # config/suitcase.php
php artisan vendor:publish --tag=suitcase-env      # .env.shared (project root)
```

Set `remote.laravel_path` and `remote.public_path` in `config/suitcase.php` to the absolute host
paths (the defaults are `/home/username/...` placeholders), and make sure `.env.shared` exists and
sets `SHARED_HOSTING=true` plus production `DB_*` / `MAIL_*` values. Configure the packer only
through `config/suitcase.php`; never edit `vendor/` files.

### Packaging

```bash
php artisan suitcase:pack                 # prompts for confirmation
php artisan suitcase:pack --skip-vendor   # do not bundle vendor/
php artisan suitcase:pack --skip-env      # do not bundle .env (it must still exist)
```

Prepare the app first - Suitcase copies what is on disk and never installs dependencies, builds
assets, or runs migrations:

```bash
composer update && npm ci && npm run build
php artisan migrate --force
php artisan suitcase:pack
```

Key rules:

- `laravel/.env` is generated from `.env.shared`; the local `.env` is never packaged.
- `public_html/index.php` and `public_html/constants.php` are always replaced by the packaged stubs
  (with the remote paths substituted), so do not hand-edit `public/index.php`.
- `.env.shared` must exist even with `--skip-env`; a missing file fails validation with exit code 1.
- Keep `QUEUE_CONNECTION=sync`, `BROADCAST_CONNECTION=log`, `CACHE_STORE=file`, and
  `SESSION_DRIVER=file` on shared hosting.

For the full option reference, extension events, custom stubs, and the deployment/troubleshooting
checklist, use the `suitcase-deployment` skill (or see the package README).
