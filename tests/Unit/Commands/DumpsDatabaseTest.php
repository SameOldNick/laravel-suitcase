<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use Illuminate\Console\OutputStyle;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use SameOldNick\LaravelSuitcase\Commands\PackForSharedHosting;
use SameOldNick\LaravelSuitcase\Contracts\Config\PackConfig;
use SameOldNick\LaravelSuitcase\Extensions\MySqlPHP;
use SameOldNick\LaravelSuitcase\Tests\TestCase;
use Spatie\DbDumper\Databases\MongoDb;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\Databases\Sqlite;
use Spatie\DbDumper\DbDumper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Unit tests for the `DumpsDatabase` concern.
 */
class DumpsDatabaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_create_db_dumper_for_maps_mysql_driver(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->assertInstanceOf(MySqlPHP::class, $this->invoke($command, 'createDbDumperFor', ['mysql']));
    }

    public function test_create_db_dumper_for_maps_pgsql_driver(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->assertInstanceOf(PostgreSql::class, $this->invoke($command, 'createDbDumperFor', ['pgsql']));
    }

    public function test_create_db_dumper_for_maps_sqlite_driver(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->assertInstanceOf(Sqlite::class, $this->invoke($command, 'createDbDumperFor', ['sqlite']));
    }

    public function test_create_db_dumper_for_maps_mongodb_driver(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->assertInstanceOf(MongoDb::class, $this->invoke($command, 'createDbDumperFor', ['mongodb']));
    }

    public function test_create_db_dumper_for_rejects_unsupported_driver(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported driver: oracle');

        $this->invoke($command, 'createDbDumperFor', ['oracle']);
    }

    public function test_create_db_dumper_builds_configured_mysql_dumper(): void
    {
        $command = $this->makePackCommand($this->packConfig());

        $dumper = $this->invoke($command, 'createDbDumper', [
            [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'dbname',
                'username' => 'user',
                'password' => 'secret',
            ],
            ['--no-create-db'],
        ]);

        $this->assertInstanceOf(MySqlPHP::class, $dumper);
    }

    public function test_get_db_config_parses_connection_url(): void
    {
        config([
            'database.connections.mysql' => [
                'driver' => 'mysql',
                'url' => 'mysql://user:secret@127.0.0.1:3307/dbname',
            ],
        ]);

        $command = $this->makePackCommand($this->packConfig());

        $config = $this->invoke($command, 'getDbConfig', ['mysql']);

        $this->assertSame('127.0.0.1', $config['host']);
        $this->assertSame(3307, $config['port']);
        $this->assertSame('dbname', $config['database']);
        $this->assertSame('user', $config['username']);
        $this->assertSame('secret', $config['password']);
    }

    public function test_dump_database_writes_dump_to_export_path(): void
    {
        config([
            'database.connections.mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'database' => 'dbname',
                'username' => 'user',
                'password' => 'secret',
            ],
        ]);

        $config = $this->packConfig();
        $command = $this->wiredPackCommand($config);

        $dumper = \Mockery::mock(DbDumper::class);
        $dumper->shouldReceive('dumpToFile')
            ->once()
            ->with($this->app->basePath('deploy').'/database.sql');

        $command->shouldReceive('createDbDumper')->once()->andReturn($dumper);

        $this->invoke($command, 'dumpDatabase');
    }

    /**
     * Build a partial mock of the pack command with IO/config wired, so
     * protected database methods can be overridden and invoked.
     */
    protected function wiredPackCommand(PackConfig $config, array $options = []): MockInterface
    {
        $command = \Mockery::mock(PackForSharedHosting::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $input = new ArrayInput($options);
        $this->setReflected($command, 'laravel', $this->app);
        $this->setReflected($command, 'input', $input);
        $this->setReflected($command, 'output', new OutputStyle($input, new BufferedOutput));
        $this->invoke($command, 'setConfig', [$config]);

        return $command;
    }
}
