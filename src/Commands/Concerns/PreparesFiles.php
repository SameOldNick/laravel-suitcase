<?php

namespace SameOldNick\LaravelSuitcase\Commands\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting;

/**
 * @mixin PackForSharedHosting
 */
trait PreparesFiles
{
    /**
     * Prepare the setup requirements.
     * Not complete.
     *
     * @param  string  $publicPath  The path to the public directory.
     * @param  array  $requirements  The setup requirements to prepare.
     * @return void
     */
    protected function prepareSetupRequirements(string $publicPath, array $requirements)
    {
        /**
         * @var PackForSharedHosting $this
         */
        $this->info('Preparing setup requirements...');

        File::put("$publicPath/setup/requirements.php", "<?php\n\nreturn ".var_export($requirements, true).";\n");

        $this->info('Setup requirements prepared successfully.');
    }

    /**
     * Creates INSTALL.txt file in the export directory.
     */
    protected function createInstallFile(): void
    {
        /**
         * @var PackForSharedHosting $this
         */
        $this->info('Creating INSTALL.txt file...');

        $path = $this->getConfig()->getExportPath().'/INSTALL.txt';

        // TODO: Pull from stubs
        $databaseStep = $this->getConfig()->getDbDumpEnabled()
            ? '6. Import the database: open phpMyAdmin, select the database you created, and import the database.sql file included in this package.'
            : '6. Import your database schema and data using the tools in your hosting control panel (e.g., phpMyAdmin). A database.sql file was not included because database dumping was disabled when the package was created.';

        $steps = [
            'Perform the following steps to deploy your app:',
            '1. Upload the zip file to your shared hosting server.',
            '2. Unzip the file in the desired directory.',
            '3. Move the contents of the "laravel" directory to your Laravel root directory: '.$this->getConfig()->getRemoteLaravelPath(),
            '4. Move the contents of the "public_html" directory to your public directory: '.$this->getConfig()->getRemotePublicPath(),
            '5. Create a MySQL database and a dedicated database user in your hosting control panel, and grant the user all privileges on the database.',
            $databaseStep,
            '7. Ensure the .env file is configured correctly for production by updating the database credentials (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).',
            '8. Set the correct permissions for the storage and bootstrap/cache directories.',
            '9. Add the following Cron job to your server:',
            '   * * * * * php '.$this->getConfig()->getRemoteLaravelPath().'/artisan schedule:run >> /dev/null 2>&1',
        ];

        File::put($path, implode("\n", $steps));

        $this->info('INSTALL.txt file created successfully.');
    }

    /**
     * Update the constants.php file.
     */
    protected function updateConstantsFile(string $constantsPath): void
    {
        /**
         * @var PackForSharedHosting $this
         */
        $this->info('Updating constants.php file...');

        $contents = File::get($constantsPath);

        $contents = Str::replace('{{ laravelRootDir }}', "'".addslashes($this->getConfig()->getRemoteLaravelPath())."'", $contents);

        File::put($constantsPath, $contents);

        $this->info('Updated constants.php file successfully.');
    }

    /**
     * Update the .env file with the provided environment variables.
     */
    protected function updateEnvFile(string $envPath, array $envVariables): void
    {
        /**
         * @var PackForSharedHosting $this
         */
        $this->info('Updating .env file...');

        $contents = File::get($envPath);

        $bar = $this->output->createProgressBar(count($envVariables));

        $bar->setFormat('verbose');
        $bar->start();

        foreach ($envVariables as $key => $value) {
            $bar->setMessage("Updating '$key' variable...");

            $contents = $this->setEnvVariable($contents, $key, $this->normalizeEnvValue($value));
            $bar->advance();
        }

        $bar->finish();

        File::put($envPath, $contents);

        $this->newLine();

        $this->info("Updated '{$envPath} file successfully.");
    }

    /**
     * Normalize the environment variable value for .env file.
     *
     * @param  mixed  $value
     */
    protected function normalizeEnvValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            return implode(',', $value);
        } elseif (is_string($value)) {
            return str_replace(['"', "'"], '', $value);
        } elseif (is_null($value)) {
            return 'null';
        }

        return (string) $value;
    }

    /**
     * Set the environment variable in the .env file contents.
     */
    protected function setEnvVariable(string $contents, string $key, string $value): string
    {
        if (preg_match("/^$key=/m", $contents)) {
            $contents = preg_replace("/^$key=.*/m", "$key=$value", $contents);
        } else {
            $contents .= PHP_EOL."$key=$value".PHP_EOL;
        }

        return $contents;
    }
}
