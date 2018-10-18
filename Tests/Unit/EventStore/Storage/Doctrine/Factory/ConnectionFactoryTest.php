<?php
namespace Neos\EventSourcing\Tests\Unit\EventStore\Doctrine\Factory;

use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\Storage\Doctrine\Factory\ConnectionFactory;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ConnectionFactoryTest extends UnitTestCase
{

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    public function setUp()
    {
        $this->connectionFactory = new ConnectionFactory();
        $this->inject($this->connectionFactory, 'defaultFlowDatabaseConfiguration', ['driver' => 'pdo_mysql', 'host' => 'defaultHost']);
    }

    /**
     * @test
     */
    public function createMergesDefaultConfigurationWithSpecifiedBackendOptions()
    {
        $connection = $this->connectionFactory->create(['backendOptions' => ['host' => 'customHost']]);
        $expectedParams = ['driver' => 'pdo_mysql', 'host' => 'customHost'];
        $this->assertSame($expectedParams, $connection->getParams());
    }

    /**
     * @test
     */
    public function createReturnsADifferentInstanceForDifferentOptions()
    {
        $connection1 = $this->connectionFactory->create(['foo' => 'Foo']);
        $connection2 = $this->connectionFactory->create(['foo' => 'Bar']);
        $this->assertNotSame($connection1, $connection2);
    }

    /**
     * @test
     */
    public function createReturnsTheSameInstanceForTheSameOptions()
    {
        $connection1 = $this->connectionFactory->create(['foo' => 'Foo']);
        $connection2 = $this->connectionFactory->create(['foo' => 'Foo']);
        $this->assertSame($connection1, $connection2);
    }
}
