<?php

return [
    // Name of the export folder (relative to base_path)
    'export_dir' => 'deploy',

    // Name of the zip file to create
    'zip_name' => 'laravel_shared_hosting_pack.zip',

    'db_dump' => [
        // Whether to include a MySQL database dump
        'enabled' => true,

        // Which DB connection to use
        'connection' => 'mysql',

        'extra_options' => [
            // Skips the CREATE DATABASE statement
            '--no-create-db',
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
];
