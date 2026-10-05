---
name: suitcase-deployment
description: Package and deploy a Laravel application to shared hosting (cPanel, DirectAdmin) with Laravel Suitcase. Use when configuring config/suitcase.php or .env.shared, running suitcase:pack, setting remote deployment paths, customizing packaged stubs, hooking pack pipeline events, or troubleshooting a Suitcase deployment.
license: MIT
---

# Laravel Suitcase Deployment

Use this skill to package a Laravel application for shared hosting and to deploy or debug the
resulting ZIP.

## When to use this skill

- Packaging a Laravel app for shared hosting with `php artisan suitcase:pack`.
- Configuring `config/suitcase.php` (paths, includes/excludes, database dumping, stubs).
- Preparing the `.env.shared` environment file.
- Customizing exported environment variables or hooking into the pack pipeline.
- Troubleshooting a Suitcase deployment (exit code 1, HTTP 500, missing dump, storage symlink).

## What the packer produces

`suitcase:pack` writes a ZIP (default `laravel_shared_hosting_pack.zip`) containing:

- `laravel/` - the application: `app`, `bootstrap`, `config`, `database`, `lang`, `resources`,
  `routes`, a `storage/` skeleton, `vendor/`, `artisan`, `composer.json`, `composer.lock`, and a
  generated `.env`.
- `public_html/` - the contents of `public/` plus the shared-hosting `index.php` and
  `constants.php`.
- `INSTALL.txt` - generated deployment steps.
- an optional database dump (for example `database.sql`).

## Requirements

- PHP 8.1+ with the `zip` and `pdo` extensions (plus the PDO driver for the database).
- Laravel 10, 11, 12, or 13.
- Shared hosting cannot run asynchronous queues, broadcasting/WebSockets, or long-running Artisan
  commands/daemons.

## Setup

```bash
composer require sameoldnick/laravel-suitcase

php artisan vendor:publish --tag=suitcase-config   # -> config/suitcase.php
php artisan vendor:publish --tag=suitcase-env      # -> .env.shared (project root)
```

Configure the packer only through `config/suitcase.php` (root key `suitcase`); never edit `vendor/`.

## File layout and conventions

| Path | Purpose |
| --- | --- |
| `config/suitcase.php` | Packer configuration (root key `suitcase`). |
| `.env.shared` | Source environment file. Must exist before packing or validation exits 1, even with `--skip-env`. |
| `deploy/` | Export directory (`export_dir`); deleted and regenerated on every pack. |
| `<zip_name>` | Output ZIP written to the project root. |

Rules:

- The Laravel export copies from `base_path()` using `include.laravel` / `exclude.laravel`; the
  public export copies from `public/` using `include.public` / `exclude.public`.
- Patterns are `Str::is` globs, matched against both the relative path and the basename. `[]` or
  `['*']` means "everything"; a single `'*'` is treated as "no filter".
- `public/` is never copied into `laravel/`; it is exported to `public_html/` instead.
- `public_html/index.php` and `public_html/constants.php` are always replaced by the packaged stubs
  (`stubs/index.php.stub`, `stubs/constants.php.stub`) with the remote paths substituted. Do not
  hand-edit `public/index.php` expecting it to ship.
- `laravel/.env` is generated from `env_file`; the local `.env` is excluded by default.
- `suitcase:pack` validates before any work: the export dir, ZIP name, env file path, and stub paths
  must be set, and the env and stub files must exist. A failure prints `Configuration error` and
  returns a non-zero exit code.

Default include/exclude lists (from the published config):

```php
'include' => [
    'laravel' => [
        'app/*', 'bootstrap/*', 'config/*', 'database/*', 'lang/*', 'resources/*',
        'routes/*', 'storage/*', 'vendor/*', 'artisan', 'composer.json', 'composer.lock',
    ],
    'public' => ['*'],
],
'exclude' => [
    'laravel' => [
        'laravel_shared_hosting_pack.zip', '.env', 'public/*', 'node_modules/*', '.yarn/*',
        'tests/*', '.git/*', 'deploy/*', 'storage/logs/*',
        'storage/framework/cache/*', 'storage/framework/sessions/*',
        'storage/framework/testing/*', 'storage/framework/views/*',
    ],
    'public' => ['hot', 'setup/*', 'storage/*', '.gitignore', '.gitattributes'],
],
```

## Configuration reference (`config/suitcase.php`)

| Key | Default | Notes |
| --- | --- | --- |
| `export_dir` | `deploy` | Local export directory, relative to `base_path()`. |
| `zip_name` | `laravel_shared_hosting_pack.zip` | Output ZIP, relative to `base_path()`. |
| `env_file` | `.env.shared` | Source env file, relative to `base_path()`. |
| `remote.laravel_path` | `/home/username/laravel` | Absolute Laravel root path on the host. **Set this.** |
| `remote.public_path` | `/home/username/public_html` | Absolute web root path on the host. **Set this.** |
| `export.laravel_path` | `null` | Local Laravel export dir; defaults to `<export_dir>/laravel`. Env: `EXPORT_LARAVEL_PATH`. |
| `export.public_path` | `null` | Local public export dir; defaults to `<export_dir>/public_html`. Env: `EXPORT_PUBLIC_PATH`. |
| `db_dump.enabled` | `true` | Whether to include a database dump. |
| `db_dump.connections.<name>.dump_path` | `database.sql` | Dump path relative to `export_dir`; falls back to `database-<name>.sql` when omitted. |
| `db_dump.connections.<name>.extra_options` | `['--no-create-db']` | Extra dumper options. |
| `db_dump.dumpers.<driver>` | driver default | Class implementing `Spatie\DbDumper\DbDumper` (for example `MySqlPHP::class`). Supported drivers: `mysql`, `mariadb`, `pgsql`, `sqlite`, `mongodb`. |
| `include.laravel` / `exclude.laravel` | see above | Laravel export globs. |
| `include.public` / `exclude.public` | see above | Public export globs. |
| `stubs.index` / `stubs.constants` | packaged stubs | Optional stub file overrides (use absolute paths). Default to the packaged `stubs/` files. |
| `shared_hosting` | `env('SHARED_HOSTING', false)` | Enables the storage-symlink route; also written to the exported `.env`. |
| `storage_route` | `env('STORAGE_ROUTE', '/storage')` | URL that runs `storage:link`. |
| `skip.env` | `env('SKIP_ENV', false)` | Skip exporting `.env`. |
| `skip.vendor` | `env('SKIP_VENDOR', false)` | Skip exporting `vendor/`. Set this when `vendor/` is installed on the host. |

Do not store host-specific secrets in `config/suitcase.php` that you would not ship, because
`config/` is included in the export.

## Environment file (`.env.shared`)

The exported `laravel/.env` is generated from `.env.shared`, then these values are overridden or
inserted:

```dotenv
APP_KEY=<fresh base64 key, regenerated on every pack>
APP_ENV=production
APP_DEBUG=false
SHARED_HOSTING=true
```

Set `DB_*`, `MAIL_*`, `APP_URL`, and other production values in `.env.shared`. Keep
`QUEUE_CONNECTION=sync`, `BROADCAST_CONNECTION=log`, `CACHE_STORE=file`, and `SESSION_DRIVER=file`
(the packaged stub defaults) because shared hosting cannot run queue workers or broadcasting servers.

## Packaging

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

## Customizing and extending

Override or add exported environment variables. The variables array is passed by reference:

```php
use Illuminate\Support\Facades\Event;

Event::listen('suitcase.env.variables', function (array &$variables) {
    $variables['APP_URL'] = 'https://example.com';
    $variables['CACHE_STORE'] = 'file';
});
```

Lifecycle events are dispatched in this order; each payload also contains the resolved `config`
(`SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig`): `suitcase.preparing`,
`suitcase.directories.prepared`, `suitcase.database.dumped`, `suitcase.files.exported`,
`suitcase.env.updated`, `suitcase.install.file.created`, `suitcase.zipped`, `suitcase.completed`.

To replace behavior, bind the package contracts in a service provider
(`Contracts\Config\PackConfig`, `Contracts\Config\Options`, `Contracts\EnvVariables`,
`Contracts\Outputter`) or implement `Contracts\PackPipelineStep`.

## Deploying the package

The generated `INSTALL.txt` lists these steps:

1. Upload the ZIP to the shared hosting server and unzip it.
2. Move the contents of `laravel/` to `remote.laravel_path`.
3. Move the contents of `public_html/` to `remote.public_path`.
4. Create a database and a dedicated user in the control panel, and grant all privileges.
5. Import `database.sql` (unless database dumping was disabled).
6. Update the database credentials in the deployed `.env`.
7. Set permissions on `storage/` and `bootstrap/cache/`.
8. Add the scheduler cron: `* * * * * php /path/to/laravel/artisan schedule:run >> /dev/null 2>&1`.

## Troubleshooting

- **`Configuration error` / exit code 1** - the resolved `env_file` (`.env.shared`) is missing; it
  must exist even with `--skip-env`. Also check `export_dir`, `zip_name`, and the stub paths.
- **HTTP 500 after deploy** - read `storage/logs/laravel.log` for the exception and stack trace, and
  the control panel's PHP error log.
- **`storage` symlink missing** - with no shell access, request `storage_route` (default `/storage`)
  so the app runs `storage:link`.
- **No database dump in the ZIP** - `db_dump.enabled` is false, or the connection name is not listed
  in `db_dump.connections`.
- **Unsupported driver** - `db_dump.dumpers` has no entry for the connection's driver, or the
  configured class does not implement `Spatie\DbDumper\DbDumper`.
- **Vendor missing on the host** - `--skip-vendor` was used but `composer install` was not run on
  the server.

## Best practices

- Always set `remote.laravel_path` and `remote.public_path`; the defaults are placeholders.
- Prepare the app (dependencies, built assets, migrations) before packing.
- Exclude secrets from the ZIP (for example with `--skip-env` or custom `exclude.laravel` globs)
  when the package may be shared or archived.
- The default MySQL and MariaDB dumper is the pure-PHP `MySqlPHP` class, so no `mysqldump` binary is
  required on the packing machine.
- After packing, verify the ZIP contains `INSTALL.txt`, `public_html/index.php`,
  `public_html/constants.php`, `laravel/.env`, and (when enabled) the database dump, and that it
  does not contain the local `.env`, `node_modules/`, `.git/`, or `tests/`.
