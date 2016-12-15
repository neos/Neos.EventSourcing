<?php
namespace Neos\Cqrs\EventStore\Storage\Doctrine\Factory;

/*
 * This file is part of the Neos.EventStore.DatabaseStorageAdapter package.
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
     * @return Connection
     */
    public static function create($options)
    {
        $config = new Configuration();
        $connectionParams = $options['backendOptions'];
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
