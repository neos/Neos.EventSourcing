<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventStore\Storage\Doctrine\Factory;

use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Neos\EventSourcing\EventStore\Storage\Doctrine\Factory\ConnectionFactory;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Flow\Persistence\Doctrine\DataTypes\ObjectArray;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;

class ConnectionFactoryTest extends UnitTestCase
{
    private const SQLITE_BACKEND_OPTIONS = ['driver' => 'pdo_sqlite', 'memory' => true];

    private function createFactory(array $flowPersistenceConfiguration): ConnectionFactory
    {
        $factory = new ConnectionFactory();
        ObjectAccess::setProperty($factory, 'flowPersistenceConfiguration', $flowPersistenceConfiguration, true);
        return $factory;
    }

    private function flowConfigurationWithMappingTypes(): array
    {
        return [
            'backendOptions' => self::SQLITE_BACKEND_OPTIONS,
            'doctrine' => [
                'dbal' => [
                    'mappingTypes' => [
                        'flow_json_array' => ['dbType' => 'flow_json_array', 'className' => JsonArrayType::class],
                        'objectarray' => ['dbType' => 'array', 'className' => ObjectArray::class],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function createRegistersMappingTypesFromFlowConfiguration(): void
    {
        $connection = $this->createFactory($this->flowConfigurationWithMappingTypes())->create();
        $platform = $connection->getDatabasePlatform();

        self::assertTrue(Type::hasType('flow_json_array'));
        self::assertTrue(Type::hasType('objectarray'));
        self::assertSame('flow_json_array', $platform->getDoctrineTypeMapping('flow_json_array'));
        self::assertSame('objectarray', $platform->getDoctrineTypeMapping('array'));
    }

    /**
     * @test
     */
    public function createRegistersMappingTypesFromOptions(): void
    {
        $connection = $this->createFactory(['backendOptions' => self::SQLITE_BACKEND_OPTIONS])->create([
            'mappingTypes' => [
                'es_test_custom_type' => ['dbType' => 'es_test_custom_db_type', 'className' => TextType::class],
            ],
        ]);

        self::assertTrue(Type::hasType('es_test_custom_type'));
        self::assertSame('es_test_custom_type', $connection->getDatabasePlatform()->getDoctrineTypeMapping('es_test_custom_db_type'));
    }

    /**
     * @test
     */
    public function mappingTypesFromOptionsOverrideFlowMappingTypesForTheSameDbType(): void
    {
        $connection = $this->createFactory($this->flowConfigurationWithMappingTypes())->create([
            'mappingTypes' => [
                'es_test_override_type' => ['dbType' => 'array', 'className' => TextType::class],
            ],
        ]);

        self::assertSame('es_test_override_type', $connection->getDatabasePlatform()->getDoctrineTypeMapping('array'));
    }

    /**
     * @test
     */
    public function createWorksWithoutDbalConfiguration(): void
    {
        $connection = $this->createFactory(['backendOptions' => self::SQLITE_BACKEND_OPTIONS])->create();

        self::assertFalse($connection->getDatabasePlatform()->hasDoctrineTypeMappingFor('flow_json_array'));
    }

    /**
     * @test
     */
    public function backendOptionsFromOptionsAreMergedOverFlowBackendOptions(): void
    {
        $connection = $this->createFactory([
            'backendOptions' => ['driver' => 'pdo_mysql', 'host' => 'flow-host', 'user' => 'flow-user'],
        ])->create([
            'backendOptions' => self::SQLITE_BACKEND_OPTIONS,
        ]);

        $params = $connection->getParams();
        self::assertSame('pdo_sqlite', $params['driver']);
        self::assertTrue($params['memory']);
        self::assertSame('flow-host', $params['host']);
        self::assertSame('flow-user', $params['user']);
    }
}
