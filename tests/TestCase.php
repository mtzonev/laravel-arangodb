<?php

namespace LaravelFreelancerNL\Aranguent\Tests;

use ArangoDBClient\Database;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\AranguentServiceProvider;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $collection = 'migrations';

    protected $connection;

    protected $collectionHandler;

    protected $databaseMigrationRepository;

    protected $packageMigrationPath;

    protected $aranguentMigrationStubPath;

    protected $laravelMigrationPath;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
//        $this->withFactories(__DIR__ . '/database/factories');

        $config = require 'config/database.php';

        $app['config']->set('database.default', 'arangodb');
        $app['config']->set('database.connections.arangodb', $config['connections']['arangodb']);
        $app['config']->set('database.connections.mysql', $config['connections']['mysql']);
        $app['config']->set('database.connections.sqlite', $config['connections']['sqlite']);

        $app['config']->set('cache.driver', 'array');

        $this->connection = DB::connection('arangodb');

        $this->createDatabase();

        $this->collectionHandler = $this->connection->getCollectionHandler();

        //Remove all collections
        $collections = $this->collectionHandler->getAllCollections(['excludeSystem' => true]);
        foreach ($collections as $collection) {
            $this->collectionHandler->drop($collection['id']);
        }
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->artisan('aranguent:convert-migrations', ['--realpath' => true, '--path' => __DIR__.'/../vendor/orchestra/testbench-core/laravel/migrations/'])->run();

        $this->artisan('migrate:install', [])->run();

        $this->databaseMigrationRepository = new DatabaseMigrationRepository($this->app['db'], $this->collection);
    }

    protected function getPackageProviders($app)
    {
        return [
            AranguentServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Aranguent' => 'LaravelFreelancerNL\Aranguent',
        ];
    }

    protected function createDatabase($database = 'aranguent_testing')
    {
        $databaseHandler = new Database();
        $response = $databaseHandler->listUserDatabases($this->connection->getArangoConnection());

        if (! in_array($database, $response['result'])) {
            $databaseHandler->create($this->connection->getArangoConnection(), $database);

            return true;
        }

        return false;
    }
}
