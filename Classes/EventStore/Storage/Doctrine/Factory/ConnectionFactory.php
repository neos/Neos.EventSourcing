<?php
namespace Neos\EventSourcing\EventStore\Storage\Doctrine\Factory;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Neos\Flow\Annotations as Flow;

/**
 * ConnectionFactory
 *
 * @Flow\Scope("singleton")
 */
class ConnectionFactory
{
    /**
     * @var array
     * @Flow\InjectConfiguration(path="EventStore.storage.options")
     */
    protected $configuration;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @return Connection
     */
    public function get()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }
        $config = new Configuration();
        $connectionParams = $this->configuration['backendOptions'];
        $this->connection = DriverManager::getConnection($connectionParams, $config);

        if (isset($this->configuration['mappingTypes']) && is_array($this->configuration['mappingTypes'])) {
            foreach ($this->configuration['mappingTypes'] as $typeName => $typeConfiguration) {
                Type::addType($typeName, $typeConfiguration['className']);
                $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping($typeConfiguration['dbType'], $typeName);
            }
        }

        return $this->connection;
    }

    /**
     * @return string
     */
    public function getStreamTableName()
    {
        return $this->configuration['eventTableName'];
    }
}
