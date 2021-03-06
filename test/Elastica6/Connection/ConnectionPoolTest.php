<?php
namespace Elastica6\Test\Connection;

use Elastica6\Connection;
use Elastica6\Connection\ConnectionPool;
use Elastica6\Connection\Strategy\StrategyFactory;
use Elastica6\Test\Base as BaseTest;

/**
 * @author chabior
 */
class ConnectionPoolTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testConstruct()
    {
        $pool = $this->createPool();

        $this->assertEquals($this->getConnections(), $pool->getConnections());
    }

    /**
     * @group unit
     */
    public function testSetConnections()
    {
        $pool = $this->createPool();

        $connections = $this->getConnections(5);

        $pool->setConnections($connections);

        $this->assertEquals($connections, $pool->getConnections());

        $this->assertInstanceOf(ConnectionPool::class, $pool->setConnections($connections));
    }

    /**
     * @group unit
     */
    public function testAddConnection()
    {
        $pool = $this->createPool();
        $pool->setConnections([]);

        $connections = $this->getConnections(5);

        foreach ($connections as $connection) {
            $pool->addConnection($connection);
        }

        $this->assertEquals($connections, $pool->getConnections());

        $this->assertInstanceOf(ConnectionPool::class, $pool->addConnection($connections[0]));
    }

    /**
     * @group unit
     */
    public function testHasConnection()
    {
        $pool = $this->createPool();

        $this->assertTrue($pool->hasConnection());
    }

    /**
     * @group unit
     */
    public function testFailHasConnections()
    {
        $pool = $this->createPool();

        $pool->setConnections([]);

        $this->assertFalse($pool->hasConnection());
    }

    /**
     * @group unit
     */
    public function testGetConnection()
    {
        $pool = $this->createPool();

        $this->assertInstanceOf(Connection::class, $pool->getConnection());
    }

    protected function getConnections($quantity = 1)
    {
        $params = [];
        $connections = [];

        for ($i = 0; $i < $quantity; ++$i) {
            $connections[] = new Connection($params);
        }

        return $connections;
    }

    protected function createPool()
    {
        $connections = $this->getConnections();
        $strategy = StrategyFactory::create('Simple');

        $pool = new ConnectionPool($connections, $strategy);

        return $pool;
    }
}
