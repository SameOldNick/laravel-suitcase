<?php

namespace SameOldNick\LaravelSuitcase\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SameOldNick\LaravelSuitcase\Tests\Support\TestServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * The temporary "Laravel app" base path used by the packer.
     *
     * @var string|null
     */
    protected static ?string $temporaryBasePath = null;

    /**
     * Prepare an isolated base path for every test class.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$temporaryBasePath = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'lara-suitcase-'
            .bin2hex(random_bytes(6));

        if (! is_dir(static::$temporaryBasePath)) {
            mkdir(static::$temporaryBasePath, 0777, true);
        }

        // Laravel's PackageManifest (and other bootstrappers) need these
        // directories to exist before the application is created.
        foreach ([
            'bootstrap/cache',
            'storage/app',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ] as $directory) {
            $path = static::$temporaryBasePath.DIRECTORY_SEPARATOR.$directory;
            if (! is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    /**
     * Clean up the isolated base path after every test class.
     */
    public static function tearDownAfterClass(): void
    {
        if (static::$temporaryBasePath !== null) {
            static::removeDirectory(static::$temporaryBasePath);
        }

        parent::tearDownAfterClass();
    }

    /**
     * Override the application base path so packaging never touches the real
     * Testbench skeleton (or real vendor directory).
     */
    public static function applicationBasePath(): string
    {
        return static::$temporaryBasePath ?? sys_get_temp_dir();
    }

    /**
     * The test harness registers a faithful stand-in for the package provider.
     * The real `ServiceProvider` is exercised separately in `ServiceProviderTest`.
     */
    protected function getPackageProviders($app): array
    {
        return [TestServiceProvider::class];
    }

    /**
     * Seed the application environment.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.cipher', 'AES-256-CBC');
    }

    /**
     * Create a minimal Laravel-like app structure for the packer to copy.
     */
    protected function givenMinimalLaravelApp(): void
    {
        $base = $this->app->basePath();

        $files = [
            'artisan' => '<?php // artisan entry point',
            'composer.json' => '{"name":"fixture/app"}',
            'app/Models/User.php' => '<?php // User model',
            'app/Providers/AppServiceProvider.php' => '<?php // AppServiceProvider',
            'bootstrap/cache/packages.php' => '<?php // cached packages',
            'config/custom.php' => "<?php\n\nreturn ['key' => 'value'];\n",
            'routes/web.php' => '<?php // web routes',
            'resources/views/welcome.blade.php' => '<h1>Welcome</h1>',
            'public/index.php' => '<?php // original public index',
            'public/.htaccess' => 'RewriteEngine On',
            'public/build/assets/app.js' => 'console.log("hi");',
            'storage/logs/.gitignore' => '*',
            'database/seeders/DatabaseSeeder.php' => '<?php // seeder',
        ];

        foreach ($files as $path => $contents) {
            $directory = dirname($base.DIRECTORY_SEPARATOR.$path);
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($base.DIRECTORY_SEPARATOR.$path, $contents);
        }
    }

    /**
     * Create the shared-hosting environment file expected by the packer.
     */
    protected function givenSharedEnvFile(string $contents = "APP_NAME=MyApp\n"): void
    {
        file_put_contents($this->app->basePath('.env.shared'), $contents);
    }

    /**
     * List all entry names in a ZIP archive.
     *
     * @return array<int, string>
     */
    protected function zipEntries(string $zipPath): array
    {
        $zip = new \ZipArchive;
        $zip->open($zipPath);

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }

        $zip->close();

        return $entries;
    }

    /**
     * Read a single entry's contents from a ZIP archive.
     */
    protected function zipEntryContents(string $zipPath, string $entry): string|false
    {
        $zip = new \ZipArchive;
        $zip->open($zipPath);

        $contents = $zip->getFromName($entry);

        $zip->close();

        return $contents;
    }

    /**
     * Recursively remove a directory without following symlinks.
     */
    protected static function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();

            if ($item->isLink() || is_link($path)) {
                @unlink($path);
            } elseif ($item->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
