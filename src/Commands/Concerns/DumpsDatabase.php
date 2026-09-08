<?php

namespace SameOldNick\LaravelSuitcase\Commands\Concerns;

use Illuminate\Database\ConfigurationUrlParser;
use Illuminate\Support\Arr;
use SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting;
use SameOldNick\LaravelSuitcase\Extensions\MySqlPHP;
use Spatie\DbDumper\Databases\MongoDb;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\Databases\Sqlite;
use Spatie\DbDumper\DbDumper;

/**
 * @mixin PackForSharedHosting
 */
trait DumpsDatabase
{
    /**
     * Dump the database to a file.
     */
    protected function dumpDatabase(): void
    {
        /**
         * @var PackForSharedHosting $this
         */
        $outputPath = $this->getConfig()->getExportPath();

        $connections = $this->getConfig()->getDbConnections();

        foreach ($connections as $connectionName => $connectionConfig) {
            $this->dumpDatabaseConnection($connectionName, $connectionConfig, $outputPath);
        }
    }

    /**
     * Dump the database for a specific connection.
     */
    protected function dumpDatabaseConnection(string $connectionName, array $connectionConfig, string $outputPath): void
    {
        $this->info("Creating database dump for connection: {$connectionName}...");

        $dbConfig = $this->getDbConfig($connectionName);
        $dumper = $this->createDbDumper($dbConfig, $connectionConfig['extra_options'] ?? []);

        $dumpFilePath =
            $connectionConfig['dump_path'] ?
            sprintf('%s/%s', $outputPath, $connectionConfig['dump_path']) :
            sprintf('%s/database-%s.sql', $outputPath, $connectionName);

        $dumper->dumpToFile($dumpFilePath);

        $this->info("Database dump for connection {$connectionName} created at: {$dumpFilePath}");
    }

    /**
     * Get the database configuration for the specified connection.
     */
    protected function getDbConfig(string $connectionName): array
    {
        /**
         * @var PackForSharedHosting $this
         */
        $config = config("database.connections.{$connectionName}");

        return (new ConfigurationUrlParser)->parseConfiguration($config);
    }

    /**
     * Create a database dumper instance from configuration.
     */
    protected function createDbDumper(array $dbConfig, array $extraOptions): DbDumper
    {
        /**
         * @var PackForSharedHosting $this
         */

        // TODO: Skip CREATE DATABASE
        $dbDumper = $this->createDbDumperFor($dbConfig['driver'] ?? '')
            ->setHost(Arr::first(Arr::wrap($dbConfig['host'] ?? '')))
            ->setDbName($dbConfig['connect_via_database'] ?? $dbConfig['database'])
            ->setUserName($dbConfig['username'] ?? '')
            ->setPassword($dbConfig['password'] ?? '');

        if ($dbDumper instanceof MySqlPHP) {
            $dbDumper
                ->setSkipSsl($dbConfig['dump']['skip_ssl'] ?? false)
                ->setDefaultCharacterSet($dbConfig['charset'] ?? '')
                ->setGtidPurged($dbConfig['dump']['mysql_gtid_purged'] ?? 'AUTO');
        }

        if ($dbDumper instanceof MongoDb) {
            $dbDumper->setAuthenticationDatabase($dbConfig['dump']['mongodb_user_auth'] ?? '');
        }

        if (isset($dbConfig['port'])) {
            $dbDumper = $dbDumper->setPort($dbConfig['port']);
        }

        if (isset($dbConfig['unix_socket'])) {
            $dbDumper = $dbDumper->setSocket($dbConfig['unix_socket']);
        }

        foreach ($extraOptions as $extraOption) {
            $dbDumper->addExtraOption($extraOption);
        }

        return $dbDumper;
    }

    /**
     * Create a database dumper instance based on the driver.
     */
    protected function createDbDumperFor(string $driver): DbDumper
    {
        return match ($driver) {
            'mysql' => new MySqlPHP,
            'pgsql' => new PostgreSql,
            'sqlite' => new Sqlite,
            'mongodb' => new MongoDb,
            default => throw new \InvalidArgumentException("Unsupported driver: {$driver}"),
        };
    }
}
