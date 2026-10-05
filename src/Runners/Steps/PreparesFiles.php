<?php

namespace SameOldNick\LaravelSuitcase\Runners\Steps;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Runners\PackPipelineContext;

class PreparesFiles implements PackPipelineStep
{
    /**
     * Prepare the necessary files for the export process.
     */
    public function perform(PackPipelineContext $context): void
    {
        // Update index.php to point to the correct Laravel directory
        $this->updateConstantsFile($context, "{$context->getConfig()->getPublicPath()}/constants.php");

        if (! $context->getConfig()->shouldSkipEnv()) {
            // Update .env file for shared hosting
            $destinationEnvFilePath = "{$context->getConfig()->getLaravelPath()}/.env";
            $this->updateEnvFile($context, $destinationEnvFilePath, $context->getEnvVariables()->getCustomizedVariables());

            $context->getEventDispatcher()?->dispatch('suitcase.env.updated', [
                'envFilePath' => $destinationEnvFilePath,
                'envVariables' => $context->getEnvVariables()->getCustomizedVariables(),
            ]);
        }

        $this->createInstallFile($context);

        $context->getEventDispatcher()?->dispatch('suitcase.install.file.created');
    }

    /**
     * Creates INSTALL.txt file in the export directory.
     */
    protected function createInstallFile(PackPipelineContext $context): void
    {
        $context->getOutputter()->info('Creating INSTALL.txt file...');

        $path = $context->getConfig()->getExportPath().'/INSTALL.txt';

        // TODO: Pull from stubs
        $databaseStep = $context->getConfig()->getDbDumpEnabled()
            ? '6. Import the database: open phpMyAdmin, select the database you created, and import the database.sql file included in this package.'
            : '6. Import your database schema and data using the tools in your hosting control panel (e.g., phpMyAdmin). A database.sql file was not included because database dumping was disabled when the package was created.';

        $steps = [
            'Perform the following steps to deploy your app:',
            '1. Upload the zip file to your shared hosting server.',
            '2. Unzip the file in the desired directory.',
            '3. Move the contents of the "laravel" directory to your Laravel root directory: '.$context->getConfig()->getRemoteLaravelPath(),
            '4. Move the contents of the "public_html" directory to your public directory: '.$context->getConfig()->getRemotePublicPath(),
            '5. Create a MySQL database and a dedicated database user in your hosting control panel, and grant the user all privileges on the database.',
            $databaseStep,
            '7. Ensure the .env file is configured correctly for production by updating the database credentials (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).',
            '8. Set the correct permissions for the storage and bootstrap/cache directories.',
            '9. Add the following Cron job to your server:',
            '   * * * * * php '.$context->getConfig()->getRemoteLaravelPath().'/artisan schedule:run >> /dev/null 2>&1',
        ];

        if (File::put($path, implode("\n", $steps))) {
            $context->getOutputter()->info('INSTALL.txt file created successfully.');
        } else {
            $context->getOutputter()->error('Failed to create INSTALL.txt file.');
        }
    }

    /**
     * Update the constants.php file.
     */
    protected function updateConstantsFile(PackPipelineContext $context, string $constantsPath): void
    {
        $context->getOutputter()->info('Updating constants.php file...');

        $contents = File::get($constantsPath);

        $contents = Str::replace('{{ laravelPublicDir }}', "'".addslashes($context->getConfig()->getRemotePublicPath())."'", $contents);
        $contents = Str::replace('{{ laravelRootDir }}', "'".addslashes($context->getConfig()->getRemoteLaravelPath())."'", $contents);

        if (File::put($constantsPath, $contents)) {
            $context->getOutputter()->info('Updated constants.php file successfully.');
        } else {
            $context->getOutputter()->error('Failed to update constants.php file.');
        }
    }

    /**
     * Update the .env file with the provided environment variables.
     */
    protected function updateEnvFile(PackPipelineContext $context, string $envPath, array $envVariables): void
    {
        $context->getOutputter()->info('Updating .env file...');

        $contents = File::get($envPath);

        $bar = $context->getOutputter()->createProgressBar(count($envVariables));

        $bar?->setFormat('verbose');
        $bar?->start();

        foreach ($envVariables as $key => $value) {
            $bar?->setMessage("Updating '$key' variable...");

            $contents = $this->setEnvVariable($contents, $key, $this->normalizeEnvValue($value));
            $bar?->advance();
        }

        $bar?->finish();

        File::put($envPath, $contents);

        $context->getOutputter()->newLine();

        $context->getOutputter()->info("Updated '{$envPath} file successfully.");
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
