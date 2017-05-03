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
use Neos\Utility\Arrays;

/**
 * Factory for Doctrine connections
 *
 * @Flow\Scope("singleton")
 */
class ConnectionFactory
{

    /**
     * @var @Flow\InjectConfiguration(package="Neos.Flow", path="persistence.backendOptions")
     */
    protected $defaultFlowDatabaseConfiguration;

    /**
     * @param array $options
     * @return Connection
     */
    public function create(array $options)
    {
        $config = new Configuration();
        $connectionParams = $options['backendOptions'] ?? [];
        $connectionParams = Arrays::arrayMergeRecursiveOverrule($this->defaultFlowDatabaseConfiguration, $connectionParams);

        $connection = DriverManager::getConnection($connectionParams, $config);

        if (isset($options['mappingTypes']) && is_array($options['mappingTypes'])) {
            foreach ($options['mappingTypes'] as $typeName => $typeConfiguration) {
                Type::addType($typeName, $typeConfiguration['className']);
                $connection->getDatabasePlatform()->registerDoctrineTypeMapping($typeConfiguration['dbType'], $typeName);
            }
        }

        return $connection;
    }
}
