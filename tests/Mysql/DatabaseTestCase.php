<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Tests\Mysql;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Events\Dispatcher;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        if (getenv('EVOCMS_TEST_DB') !== 'mysql') {
            $this->markTestSkipped('EVOCMS_TEST_DB=mysql is not set.');
        }

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => getenv('EVOCMS_TEST_DB_HOST') ?: '127.0.0.1',
            'port' => getenv('EVOCMS_TEST_DB_PORT') ?: '3306',
            'database' => getenv('EVOCMS_TEST_DB_NAME') ?: 'evolution',
            'username' => getenv('EVOCMS_TEST_DB_USER') ?: 'root',
            'password' => getenv('EVOCMS_TEST_DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'prefix' => 'evo_',
        ]);

        $capsule->setEventDispatcher(new Dispatcher());
        $this->connection = $capsule->getConnection();
        $this->rebuild();
    }

    private function rebuild(): void
    {
        $pdo = $this->connection->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        foreach ($this->connection->select('SHOW TABLES') as $row) {
            $this->connection->statement(sprintf('DROP TABLE `%s`', array_values((array) $row)[0]));
        }

        $pdo->exec((string) file_get_contents(__DIR__ . '/../Schema/baseline.sql'));
    }
}
