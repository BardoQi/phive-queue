<?php

namespace Phive\Tests\Queue\Db\Pdo;

use Phive\Tests\Queue\AbstractQueueTest;
use Phive\Queue\Db\Pdo\PgSqlPdoQueue;

class PgSqlPdoQueueTest extends AbstractQueueTest
{
    /**
     * @var \PDO
     */
    protected static $conn;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$conn = self::createConnection();
        self::$conn->exec('DROP TABLE IF EXISTS phive_queue');
        self::$conn->exec('CREATE TABLE phive_queue(id SERIAL, eta integer NOT NULL, item text NOT NULL)');
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        self::$conn->exec('DROP TABLE IF EXISTS phive_queue');
        self::$conn = null;
    }

    public function setUp()
    {
        parent::setUp();

        self::$conn->exec('TRUNCATE phive_queue RESTART IDENTITY');
    }

    protected function createQueue()
    {
        return new PgSqlPdoQueue(self::$conn, 'phive_queue');
    }

    protected static function createConnection()
    {
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s',
            isset($GLOBALS['db_pg_host']) ? $GLOBALS['db_pg_host'] : 'localhost',
            isset($GLOBALS['db_pg_port']) ? $GLOBALS['db_pg_port'] : '5432',
            isset($GLOBALS['db_pg_db_name']) ? $GLOBALS['db_pg_db_name'] : 'phive_tests',
            isset($GLOBALS['db_pg_username']) ? $GLOBALS['db_pg_username'] : 'postgres',
            isset($GLOBALS['db_pg_password']) ? $GLOBALS['db_pg_password'] : ''
        );

        return new \Pdo($dsn);
    }
}