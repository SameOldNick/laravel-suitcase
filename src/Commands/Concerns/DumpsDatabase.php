<?php

namespace SameOldNick\LaraHostPack\Commands\Concerns;

use Illuminate\Database\ConfigurationUrlParser;
use Illuminate\Support\Arr;
use SameOldNick\LaraHostPack\Extensions\MySqlPHP;
use Spatie\DbDumper\Databases\MongoDb;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\Databases\Sqlite;
use Spatie\DbDumper\DbDumper;

/**
 * @mixin \SameOldNick\LaraHostPack\Commands\PackForSharedHosting
 */
trait DumpsDatabase
{
    /**
     * Dump the database to a file.
     */
    protected function dumpDatabase(): void
    {
        /**
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
         */
        $outputPath = $this->getConfig()->getExportPath();
        $connectionName = $this->getConfig()->getDbConnection();
        $dumpOptions = $this->getConfig()->getDbDumpOptions();

        $dbConfig = $this->getDbConfig($connectionName);
        $dumper = $this->createDbDumper($dbConfig, $dumpOptions);

        $this->info('Creating database dump...');

        $dumper->dumpToFile($outputPath.'/database.sql');

        $this->info('Database dump created.');
    }

    /**
     * Get the database configuration for the specified connection.
     */
    protected function getDbConfig(string $connectionName): array
    {
        /**
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
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
         * @var \SameOldNick\LaraHostPack\Commands\PackForSharedHosting $this
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
