## Laravel Suitcase

Laravel Suitcase (`sameoldnick/laravel-suitcase`) packages a Laravel application for deployment
on shared hosting (cPanel, DirectAdmin, and similar). The `suitcase:pack` Artisan command builds a
deployable ZIP containing:

- `laravel/` - the application (app, config, routes, a `storage/` skeleton, `vendor/`, and a generated `.env`).
- `public_html/` - the contents of `public/` plus the shared-hosting `index.php` and `constants.php`.
- `INSTALL.txt` - step-by-step deployment instructions.
- an optional SQL dump (for example `database.sql`) when database dumping is enabled.

Only use this package when the deployment target is shared hosting. Shared hosting cannot run
asynchronous queues, broadcasting/WebSockets, or long-running Artisan commands/daemons.

### Requirements

- PHP 8.1+ with the `zip` and `pdo` extensions (plus the PDO driver for the database).
- Laravel 10, 11, 12, or 13.

### Install and publish

```bash
composer require sameoldnick/laravel-suitcase

php artisan vendor:publish --tag=suitcase-config   # config/suitcase.php
php artisan vendor:publish --tag=suitcase-env      # .env.shared (project root)
```

Configure the packer only through `config/suitcase.php`; never edit `vendor/` files.

### Conventions and file layout

| Path | Purpose |
| --- | --- |
| `config/suitcase.php` | Packer configuration. The root key is `suitcase`. |
| `.env.shared` | Source environment file. It must exist before packing or validation fails (exit code 1), even when using `--skip-env`. |
| `deploy/` | Export directory (`suitcase.export_dir`); regenerated on every pack. Contains `laravel/`, `public_html/`, and `INSTALL.txt`. |
| `<zip_name>` | Output ZIP written to the project root (`suitcase.zip_name`). |

Rules the packer relies on:

- The Laravel export is built from `base_path()` using the `suitcase.include.laravel` and
  `suitcase.exclude.laravel` globs. The public export is built from `public/` using
  `suitcase.include.public` and `suitcase.exclude.public`.
- Patterns support `*` wildcards (Laravel's `Str::is`) and match both the relative path and the
  basename. An empty list, or `['*']`, means "everything".
- `public/` is never copied into `laravel/`; its contents are exported to `public_html/` instead.
- `public_html/index.php` and `public_html/constants.php` are always replaced by the packaged stubs
  (or your `suitcase.stubs.*` overrides) with the remote paths substituted. Do not hand-edit the
  app's `public/index.php` expecting it to be shipped.
- `laravel/.env` is generated from `suitcase.env_file`; the app's local `.env` is excluded from the package.

`suitcase:pack` validates the configuration before doing any work: the export directory, ZIP name,
env file path, and stub paths must be set and the files must exist. A validation failure prints a
`Configuration error` and returns a non-zero exit code.

### Configuration (`config/suitcase.php`)

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
| `db_dump.dumpers.<driver>` | driver default | Class implementing `Spatie\DbDumper\DbDumper` (for example `MySqlPHP::class`). |
| `include.laravel` / `exclude.laravel` | see above | Laravel export globs. |
| `include.public` / `exclude.public` | see above | Public export globs. |
| `stubs.index` / `stubs.constants` | packaged stubs | Optional stub file overrides (use absolute paths). Default to the packaged `stubs/` files. |
| `shared_hosting` | `env('SHARED_HOSTING', false)` | Enables the storage-symlink route; also written to the exported `.env`. |
| `storage_route` | `env('STORAGE_ROUTE', '/storage')` | URL that runs `storage:link`. |
| `skip.env` | `env('SKIP_ENV', false)` | Skip exporting `.env`. |
| `skip.vendor` | `env('SKIP_VENDOR', false)` | Skip exporting `vendor/`. Set this when `vendor/` is installed on the host. |

Do not store host-specific secrets in `config/suitcase.php` that you would not ship, because
`config/` is included in the export.

### Environment file (`.env.shared`)

The packer generates the exported `laravel/.env` from `.env.shared`, then overrides or inserts:

```dotenv
APP_KEY=<fresh base64 key, regenerated on every pack>
APP_ENV=production
APP_DEBUG=false
SHARED_HOSTING=true
```

Set `DB_*`, `MAIL_*`, `APP_URL`, and other production values in `.env.shared`. Keep
`QUEUE_CONNECTION=sync`, `BROADCAST_CONNECTION=log`, `CACHE_STORE=file`, and `SESSION_DRIVER=file`
(the packaged stub defaults) because shared hosting cannot run queue workers or broadcasting servers.

### Packaging

```bash
php artisan suitcase:pack                 # prompts for confirmation
php artisan suitcase:pack --skip-vendor   # do not bundle vendor/
php artisan suitcase:pack --skip-env      # do not bundle .env (it must still exist)
```

Prepare the app first. Suitcase copies what is on disk and never installs dependencies, builds
assets, or runs migrations:

```bash
composer update
npm ci && npm run build
php artisan migrate --force
php artisan suitcase:pack
```

### Customizing the packaged environment

Add or override exported environment variables by listening to `suitcase.env.variables`. The
listener receives the variables array by reference:

```php
use Illuminate\Support\Facades\Event;

Event::listen('suitcase.env.variables', function (array &$variables) {
    $variables['APP_URL'] = 'https://example.com';
    $variables['CACHE_STORE'] = 'file';
});
```

Lifecycle events are dispatched as the pack runs; each payload also contains the resolved `config`
(`Contracts\Config\PackConfig`): `suitcase.preparing`, `suitcase.directories.prepared`,
`suitcase.database.dumped`, `suitcase.files.exported`, `suitcase.env.updated`,
`suitcase.install.file.created`, `suitcase.zipped`, and `suitcase.completed`.

To replace behavior, bind the package contracts in a service provider
(`Contracts\Config\PackConfig`, `Contracts\Config\Options`, `Contracts\EnvVariables`,
`Contracts\Outputter`) or implement `Contracts\PackPipelineStep`.

### Best practices

- Always set `remote.laravel_path` and `remote.public_path`; the defaults are placeholders.
- Keep `SHARED_HOSTING=true` so the storage-symlink route is registered, then open
  `suitcase.storage_route` (default `/storage`) once after deploying to run `storage:link`.
- Exclude secrets from the ZIP (for example with `--skip-env` or custom `exclude.laravel` globs)
  when the package may be shared or archived.
- Do not depend on database binaries; the default MySQL and MariaDB dumper is the pure-PHP
  `MySqlPHP` class, so no `mysqldump` binary is required on the packing machine.
- After packing, verify the ZIP contains `INSTALL.txt`, `public_html/index.php`,
  `public_html/constants.php`, `laravel/.env`, and (when enabled) the database dump, and that it
  does not contain the local `.env`, `node_modules/`, `.git/`, or `tests/`.
