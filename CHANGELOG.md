# Changelog

All notable changes to Laravel Suitcase are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
