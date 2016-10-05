<?php
namespace Neos\Cqrs\EventStore\Storage\Doctrine\Schema;

/*
 * This file is part of the Neos.EventStore.DatabaseStorageAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Neos\Cqrs\EventStore\Storage\Doctrine\DataTypes\DateTimeType;

/**
 * Use this helper in a doctrine migrations script to set up the event store schema
 */
final class EventStoreSchema
{
    /**
     * Use this method when you work with a single stream strategy
     *
     * @param Schema $schema
     * @param string $name
     */
    public static function createStream(Schema $schema, string $name)
    {
        $table = $schema->createTable($name);

        // UUID4 of the stream
        $table->addColumn('stream_name', Type::TEXT);
        $table->addColumn('stream_name_hash', Type::STRING, ['length' => 32]);

        // Version of the aggregate after event was recorded
        $table->addColumn('commit_version', Type::BIGINT, ['unsigned' => true]);

        // Version of the event in the current commit
        $table->addColumn('event_version', Type::BIGINT, ['unsigned' => true]);

        // Events of the stream
        $table->addColumn('event', Type::TEXT);
        $table->addColumn('metadata', Type::TEXT);

        // Timestamp of the stream
        $table->addColumn('recorded_at', DateTimeType::DATETIME_MICRO);

        $table->setPrimaryKey(['stream_name_hash', 'commit_version', 'event_version']);
    }

    /**
     * @param Schema $schema
     * @param string $name
     */
    public static function drop(Schema $schema, string $name)
    {
        $schema->dropTable($name);
    }
}
