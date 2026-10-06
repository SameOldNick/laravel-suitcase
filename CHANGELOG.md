# Changelog

All notable changes to Laravel Suitcase are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-10-05

### Added

- Alternate config file selection for `suitcase:pack`: `--config=<path>` or the `SUITCASE_CONFIG` environment variable. The flag wins over the environment variable, and with neither present the command still uses `config/suitcase.php` over the packaged defaults.
- Config file profiles: a bare name (`--config=production`) resolves to `config/suitcase.<name>.php`, relative paths resolve against `base_path()`, and absolute paths are used unchanged.
- Partial config files: the selected file is merged over the packaged defaults, so only the keys that are set are overridden.
- The startup banner now lists every config path that was checked and marks the file in use.
- `export.public_path` and `export.laravel_path` config options (also settable with the `EXPORT_PUBLIC_PATH` and `EXPORT_LARAVEL_PATH` environment variables) to control where the package is written locally.
- `skip.env` and `skip.vendor` config options (also settable with the `SKIP_ENV` and `SKIP_VENDOR` environment variables), so `.env` and `vendor/` can be skipped without passing `--skip-env` or `--skip-vendor`.
- Configurable stub paths via the `stubs.index` and `stubs.constants` options.
- Dedicated exceptions (`ExportDirectoryNotCreated`, `ExportDirectoryNotDeleted`, `ZipFileNotCreated`, `ZipFileNotWritable`) with clearer messages when preparing directories or writing the ZIP fails.
- Laravel Boost guidelines (`resources/boost/guidelines/core.blade.php`) and a `suitcase-deployment` skill (`resources/boost/skills/suitcase-deployment/SKILL.md`).
- README documentation for configuration profiles, covering selection, path resolution, merge semantics, and the checked-paths banner.

### Changed

- Packaging moved out of the command into `PackForSharedHostingRunner`, which runs a Laravel pipeline of single-purpose steps (`PreparesZipFile`, `PreparesDirectories`, `DumpsDatabase`, `HandlesFileExport`, `PreparesFiles`, `HandlesZipping`); console output now goes through the new `Contracts\Outputter` and `ConsoleOutputter`.
- Configuration is now read through constructor attributes in `Options`. The `Contracts\Config\Repository` contract, its `ConfigRepository` implementation, and the `Contracts\Config\PackConfig` contract were removed, and `ValidatesConfig::validate()` type-hints the concrete `Config\PackConfig`.
- Merging the published `config/suitcase.php` over the packaged defaults is now recursive: nested maps merge key-by-key while list-valued options (`include.*`, `exclude.*`, `extra_options`) are replaced rather than appended, so a partial config file no longer drops sibling defaults.
- Lifecycle events no longer include the `command` key in their payload; `config` is still provided.

### Fixed

- A missing, unreadable, non-array-returning, or directory config file now fails before any export work, exits non-zero, and names the resolved absolute path, instead of terminating the process with `exit(1)`.
- `suitcase:pack` no longer aborts with "The export directory was not deleted" when the export directory does not exist yet.
- A `SUITCASE_CONFIG` value pointing at a missing file now fails instead of silently falling back to `config/suitcase.php`.
- The config file in use — the selected alternate, or the app's own `config/suitcase.php` — is no longer copied into the export or the ZIP.
- `--skip-env` and `--skip-vendor` no longer discard a config file loaded through `--config`.

## [1.1.1] - 2026-09-21

### Fixed

- The generated `constants.php` now points `LARAVEL_PUBLIC_DIR` at the configured remote public path (`remote.public_path`) instead of the local export directory.
- The shared-hosting storage helper route now reads `suitcase.storage_route` (a leftover `hostpack` key caused a fatal error when `SHARED_HOSTING=true`).
- The environment customization event is now dispatched as `suitcase.env.variables` instead of `hostpack.env.variables`.

## [1.1.0] - 2026-09-21

### Added

- Configurable database dumpers per driver (`db_dump.dumpers`), with validation that the configured class implements `Spatie\DbDumper\DbDumper`.
- MariaDB driver mapping to the pure-PHP MySQL dumper (`MySqlPHP`).
- Error reporting for file copy, write, and ZIP operations during packaging.

### Fixed

- The generated `constants.php` now derives `LARAVEL_PUBLIC_DIR` from the configured public path instead of hardcoding `__DIR__`.
- The shared-hosting front controller now applies the public path after the HTTP Kernel is created, so the public directory is set correctly.

### Changed

- Documentation references updated to Laravel 13.

## [1.0.0] - 2026-09-08

### Added

- `suitcase:pack` Artisan command that packages a Laravel application for deployment on shared hosting (cPanel, DirectAdmin, etc.).
- Deployable ZIP output containing a `laravel/` directory, a `public_html/` directory, and an `INSTALL.txt` with step-by-step deployment instructions.
- Publishable configuration file (`config/suitcase.php`) via `vendor:publish --tag=suitcase-config`.
- Configurable file inclusion/exclusion patterns for the `laravel/` and `public_html/` directories.
- Publishable shared-hosting environment file (`.env.shared`) via `vendor:publish --tag=suitcase-env`.
- Shared-hosting front controller (`public/index.php`) and `constants.php` that point to the remote Laravel root directory.
- Automatic database dumping with configurable connection and support for MySQL, PostgreSQL, SQLite, and MongoDB drivers.
- Pure-PHP MySQL dumping via `ifsnop/mysqldump-php`, so no `mysqldump` binary is required on the machine running the pack.
- Per-connection database dump options: custom dump path, extra `mysqldump` options, SSL, GTID purged, character set, and MongoDB authentication database.
- Shared-hosting `.env` generation: creates an `APP_KEY`, forces `APP_ENV=production` and `APP_DEBUG=false`, and sets `SHARED_HOSTING=true`.
- `--skip-vendor` and `--skip-env` options for the `suitcase:pack` command.
- Configuration validation that verifies required paths and files before packaging.
- Extensible lifecycle events dispatched while packing (`suitcase.*`).
- Storage symlink helper route for hosting environments without shell access.
- Documentation, unit/feature tests, and automated CI (PHP 8.2–8.4, Laravel 11–13, Laravel Pint formatting).
