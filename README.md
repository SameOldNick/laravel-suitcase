# Laravel Suitcase

[![codecov](https://codecov.io/gh/SameOldNick/laravel-suitcase/graph/badge.svg?token=SEKDQOQAHW)](https://codecov.io/gh/SameOldNick/laravel-suitcase)
[![Tests](https://github.com/SameOldNick/laravel-suitcase/actions/workflows/tests.yml/badge.svg)](https://github.com/SameOldNick/laravel-suitcase/actions/workflows/tests.yml)

Laravel Suitcase helps you package your Laravel app for deployment on shared hosting, handling environment setup, file structure, and more.

## Quick Start

1. Install Laravel Suitcase:
   ```bash
   composer require sameoldnick/laravel-suitcase
   ```
2. Publish the config and environment files:
   ```bash
   php artisan vendor:publish --tag=suitcase-config
   php artisan vendor:publish --tag=suitcase-env
   ```
3. Update `config/suitcase.php` with your shared hosting paths (at minimum `remote.laravel_path` and `remote.public_path`) and any other customizations.
4. Generate an application key for shared hosting:
   ```bash
   php artisan --env=shared key:generate
   ```
5. Edit `.env.shared` with your database and mail settings.
6. Prepare your app for production (update dependencies, build assets, run migrations).
7. Package your app:
   ```bash
   php artisan suitcase:pack
   ```
8. Deploy the generated ZIP to your shared hosting and follow the `INSTALL.txt` instructions inside.

## Table of Contents

- [Limitations](#limitations)
- [Requirements](#requirements)
- [Detailed Setup](#detailed-setup)
  - [Environment File](#environment-file)
    - [Database Configuration](#database-configuration)
    - [Mail Configuration](#mail-configuration)
    - [Additional Configuration](#additional-configuration)
  - [Preparation](#preparation)
  - [Package the Laravel App](#package-the-laravel-app)

## Limitations

Shared hosting does not support some features of Laravel, such as:

- [Asynchronous queues](https://laravel.com/docs/12.x/queues)
- [Broadcasting and WebSockets](https://laravel.com/docs/12.x/broadcasting)
- [Artisan commands](https://laravel.com/docs/12.x/artisan)

If your Laravel app requires these features, you will need a [VPS](https://www.google.com/search?q=virtual+private+server) or [dedicated server](https://www.google.com/search?q=dedicated+server).

## Requirements

- [PHP v8.1 or higher](https://www.php.net/downloads.php)
- [Laravel 10.x, 11.x, or 12.x](https://laravel.com/docs/12.x)

**Required PHP extensions:**

- [pdo](https://www.php.net/manual/en/book.pdo.php)
- [pdo_mysql (or pdo_sqlite, pdo_pgsql, etc. for your DB)](https://www.php.net/manual/en/pdo.drivers.php)
- [zip](https://www.php.net/manual/en/book.zip.php)

## Detailed Setup

### 1. Install the Package

Install Laravel Suitcase using Composer. This will add it to your Laravel project's dependencies.

```bash
composer require sameoldnick/laravel-suitcase
```

### 2. Publish the Config and Environment Files

Publish the configuration file to `config/suitcase.php` and the shared environment file to your project root. You can customize these as needed.

```bash
php artisan vendor:publish --tag=suitcase-config
php artisan vendor:publish --tag=suitcase-env
```

### 3. Update the Config File

Open `config/suitcase.php` and update it to match your shared hosting setup. At a minimum, set the `remote.laravel_path` and `remote.public_path` options to the paths on your shared hosting server. You can also customize the `export_dir`, `zip_name`, and `env_file` options as needed.

### 4. Generate the Application Key

Generate a unique application key for your shared hosting environment. This is important for security—do not reuse keys across installations.

```bash
php artisan --env=shared key:generate
```

**Important:** Use a different key for each installation of your Laravel app. Do not use the same key for every installation.

### 5. Edit the Environment File

Edit the `.env.shared` file to configure your environment variables for the shared hosting environment.

#### Database Configuration

If you haven't already, create a database in your shared hosting account. Set the database credentials in the `.env.shared` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=username_db
DB_USERNAME=username_user
DB_PASSWORD=secret
```

#### Mail Configuration

Set the mail configuration in the `.env.shared` file. If you have an email account with your hosting provider, you can use SMTP:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=127.0.0.1
MAIL_PORT=465
MAIL_USERNAME="username@yourdomain.com"
MAIL_PASSWORD=secret
MAIL_FROM_ADDRESS="username@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Or use sendmail:

```env
MAIL_MAILER=sendmail
```

#### Additional Configuration

The `SHARED_HOSTING` variable needs to be set so the Laravel app can run properly in a shared hosting environment:

```env
SHARED_HOSTING=true
```

Ensure that the correct environment variables are set. You can reference the `.env` file used for local development to see what variables should be set.

### 6. Prepare Your App for Production

Before packaging your Laravel app, make sure it is production-ready. Laravel Suitcase does not run database migrations or build frontend assets; it copies what is available. Be sure to:

- Update Composer dependencies: `composer update`
- Update NodeJS packages: `npm i`
- Build frontend assets: `npm run build`
- Run database migrations: `php artisan migrate`

### 7. Package the Laravel App

When you're ready, package the Laravel app by running:

```bash
php artisan suitcase:pack
```

Packaging may take several minutes. A ZIP file will be created in the root folder of your Laravel app. Follow the instructions in the `INSTALL.txt` file (inside the ZIP) to deploy to shared hosting.
