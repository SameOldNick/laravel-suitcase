<?php

namespace SameOldNick\LaravelSuitcase\Tests\Unit\Commands;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SameOldNick\LaravelSuitcase\Contracts\PackPipelineStep;
use SameOldNick\LaravelSuitcase\Extensions\MySqlPHP;
use SameOldNick\LaravelSuitcase\Runners\Steps\DumpsDatabase;
use SameOldNick\LaravelSuitcase\Support\Outputters\OutputRecorder;
use SameOldNick\LaravelSuitcase\Tests\TestCase;
use Spatie\DbDumper\Databases\MongoDb;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\Databases\Sqlite;
use Spatie\DbDumper\DbDumper;

/**
 * Unit tests for the `DumpsDatabase` pipeline step.
 */
class DumpsDatabaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function createStep(): PackPipelineStep
    {
        return new DumpsDatabase;
    }

    public function test_create_db_dumper_for_maps_mysql_driver(): void
    {
        $step = $this->createStep();

        $this->assertInstanceOf(MySqlPHP::class, $this->invoke($step, 'createDbDumperFor', ['mysql']));
    }

    public function test_create_db_dumper_for_maps_mariadb_driver(): void
    {
        $step = $this->createStep();

        $this->assertInstanceOf(MySqlPHP::class, $this->invoke($step, 'createDbDumperFor', ['mariadb']));
    }

    public function test_create_db_dumper_for_maps_pgsql_driver(): void
    {
        $step = $this->createStep();

        $this->assertInstanceOf(PostgreSql::class, $this->invoke($step, 'createDbDumperFor', ['pgsql']));
    }

    public function test_create_db_dumper_for_maps_sqlite_driver(): void
    {
        $step = $this->createStep();

        $this->assertInstanceOf(Sqlite::class, $this->invoke($step, 'createDbDumperFor', ['sqlite']));
    }

    public function test_create_db_dumper_for_maps_mongodb_driver(): void
    {
        $step = $this->createStep();

        $this->assertInstanceOf(MongoDb::class, $this->invoke($step, 'createDbDumperFor', ['mongodb']));
    }

    public function test_create_db_dumper_for_rejects_unsupported_driver(): void
    {
        $step = $this->createStep();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported driver: oracle');

        $this->invoke($step, 'createDbDumperFor', ['oracle']);
    }

    public function test_create_db_dumper_builds_configured_mysql_dumper(): void
    {
        $step = $this->createStep();

        $dumper = $this->invoke($step, 'createDbDumper', [
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

        $step = $this->createStep();

        $config = $this->invoke($step, 'getDbConfig', ['mysql']);

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

        $config = $this->packConfig([
            'db_dump.enabled' => true,
            'db_dump.connections' => [
                'mysql' => [
                    'dump_path' => 'database.sql',
                    'extra_options' => [],
                ],
            ],
        ]);
        $context = $this->createContext($config);

        $step = \Mockery::mock(DumpsDatabase::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $dumper = \Mockery::mock(DbDumper::class);
        $dumper->shouldReceive('dumpToFile')
            ->once()
            ->with($this->app->basePath('deploy').'/database.sql');

        $step->shouldReceive('createDbDumper')->once()->andReturn($dumper);

        $step->perform($context);
    }

    public function test_dump_database_is_skipped_when_disabled(): void
    {
        $config = $this->packConfig(['db_dump.enabled' => false]);
        $outputter = new OutputRecorder;
        $context = $this->createContext($config, outputter: $outputter);

        $step = \Mockery::mock(DumpsDatabase::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $step->shouldNotReceive('dumpDatabaseConnection');

        $step->perform($context);

        $messages = array_column($outputter->getMessages(), 'message');
        $this->assertContains('Skipping database dump as per configuration.', $messages);
    }
}
