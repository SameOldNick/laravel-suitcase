<?php

use SameOldNick\LaravelSuitcase\Extensions\MySqlPHP;

return [
    // Name of the export folder (relative to base_path)
    'export_dir' => 'deploy',

    // Name of the zip file to create
    'zip_name' => 'laravel_shared_hosting_pack.zip',

    'db_dump' => [
        // Whether to include a MySQL database dump
        'enabled' => true,

        'connections' => [
            // Each database connection configuration
            // The key is the database connection name (as defined in config/database.php)
            'mysql' => [
                // Path to the SQL dump file (relative to export_dir)
                'dump_path' => 'database.sql',

                // Additional options to pass to mysqldump
                'extra_options' => [
                    // Skips the CREATE DATABASE statement
                    '--no-create-db',
                ],
            ],
        ],

        'dumpers' => [
            // Each database driver can have its own dumper class.
            // The key is the database driver name (as defined in config/database.php)
            // The value is the fully qualified class name of the dumper class that implements the Spatie\DbDumper\DbDumper interface.
            'mysql' => MySqlPHP::class,
            'mariadb' => MySqlPHP::class,
        ],
    ],

    // Environment file to use for the package
    'env_file' => '.env.shared',

    // These options are used to define the folder paths on the shared hosting server.
    'remote' => [
        // Path to the Laravel directory on the shared hosting server
        'laravel_path' => '/home/username/laravel',

        // Path to the public directory on the shared hosting server
        'public_path' => '/home/username/public_html',
    ],

    // Directories and files to include in the package
    'include' => [
        'laravel' => [
            'app/*',
            'bootstrap/*',
            'config/*',
            'database/*',
            'lang/*',
            'resources/*',
            'routes/*',
            'storage/*',
            'vendor/*',
            'artisan',
            'composer.json',
            'composer.lock',
        ],
        'public' => [
            '*',
        ],
    ],

    // Directories and files to exclude from the package
    'exclude' => [
        'laravel' => [
            'laravel_shared_hosting_pack.zip',
            '.env',
            'public/*',
            'node_modules/*',
            '.yarn/*',
            'tests/*',
            '.git/*',
            'deploy/*',
            'storage/logs/*',
            'storage/framework/cache/*',
            'storage/framework/sessions/*',
            'storage/framework/testing/*',
            'storage/framework/views/*',
        ],

        'public' => [
            'hot',
            'setup/*',
            'storage/*',
            '.gitignore',
            '.gitattributes',
        ],
    ],

    // Indicates if running in shared hosting environment
    'shared_hosting' => env('SHARED_HOSTING', false),

    // Route to create a symbolic link to the storage directory
    'storage_route' => env('STORAGE_ROUTE', '/storage'),

    // Where the packaged files are written on this machine. These are local
    // paths used while building the package, unlike the `remote` paths above
    // which describe the shared hosting server.
    'export' => [
        // Directory the exported public_html files are written to.
        // When null, defaults to `<export_dir>/public_html`.
        // Can also be set with the EXPORT_PUBLIC_PATH environment variable.
        'public_path' => env('EXPORT_PUBLIC_PATH'),

        // Directory the exported Laravel application is written to.
        // When null, defaults to `<export_dir>/laravel`.
        // Can also be set with the EXPORT_LARAVEL_PATH environment variable.
        'laravel_path' => env('EXPORT_LARAVEL_PATH'),
    ],

    // Options for skipping certain files or directories during export.
    'skip' => [
        // If true, the .env file will be skipped during export.
        'env' => env('SKIP_ENV', false),
        // If true, the vendor directory will be skipped during export.
        'vendor' => env('SKIP_VENDOR', false),
    ],
];
