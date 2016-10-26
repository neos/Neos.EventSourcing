<?php
namespace Neos\Cqrs\EventStore\Storage\Doctrine;

/*
 * This file is part of the Neos.EventStore.DatabaseStorageAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Neos\Cqrs\Event\EventTypeResolver;
use Neos\Cqrs\EventStore\EventStream;
use Neos\Cqrs\EventStore\Exception\ConcurrencyException;
use Neos\Cqrs\EventStore\ExpectedVersion;
use Neos\Cqrs\EventStore\Storage\Doctrine\Factory\ConnectionFactory;
use Neos\Cqrs\EventStore\Storage\EventStorageInterface;
use Neos\Cqrs\EventStore\WritableEvents;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Utility\Now;

/**
 * Database event storage, for testing purpose
 */
class DoctrineEventStorage implements EventStorageInterface
{
    /**
     * @var ConnectionFactory
     * @Flow\Inject
     */
    protected $connectionFactory;

    /**
     * @var PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var EventTypeResolver
     */
    protected $eventTypeResolver;

    /**
     * @Flow\Inject
     * @var Now
     */
    protected $now;

    /**
     * @var Connection
     */
    private $connection;

    protected function initializeObject()
    {
        $this->connection = $this->connectionFactory->get();
    }

    public function load(string $streamName): EventStream
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $query = $queryBuilder
            ->select('*')
            ->from($this->connectionFactory->getStreamTableName())
            ->andWhere('stream = :stream')
            ->orderBy('id', 'ASC')
            ->addOrderBy('version', 'ASC')
            ->setParameter('stream', $streamName, \PDO::PARAM_STR);

        $streamIterator = new DoctrineStreamIterator($query->execute());
        return new EventStream($streamIterator, $this->getStreamVersion($streamName));
    }

    /**
     * @param string $streamName
     * @param WritableEvents $events
     * @param int $expectedVersion
     * @return void
     * @throws ConcurrencyException|\Exception
     */
    public function commit(string $streamName, WritableEvents $events, int $expectedVersion = ExpectedVersion::ANY)
    {
        $this->connection->beginTransaction();
        $actualVersion = $this->getStreamVersion($streamName);
        $this->verifyExpectedVersion($actualVersion, $expectedVersion);

        foreach ($events as $event) {
            $this->connection->insert(
                $this->connectionFactory->getStreamTableName(),
                [
                    'stream' => $streamName,
                    'version' => ++$actualVersion,
                    'type' => $event->getType(),
                    'payload' => json_encode($event->getData(), JSON_PRETTY_PRINT),
                    'metadata' => json_encode($event->getMetadata(), JSON_PRETTY_PRINT),
                    'recordedat' => $this->now
                ],
                [
                    'version' => \PDO::PARAM_INT,
                    'recordedat' => Type::DATETIME,
                ]
            );
        }
        $this->connection->commit();
    }

    /**
     * @param string $streamName
     * @return int
     */
    private function getStreamVersion(string $streamName): int
    {
        $version = $this->connection->fetchColumn('SELECT MAX(version) FROM ' . $this->connectionFactory->getStreamTableName() . ' WHERE stream = ?', [$streamName]);
        return $version !== null ? (int)$version : -1;
    }

    private function verifyExpectedVersion(int $actualVersion, int $expectedVersion)
    {
        if ($expectedVersion === ExpectedVersion::ANY) {
            return;
        }
        if ($expectedVersion === $actualVersion ) {
            return;
        }
        throw new ConcurrencyException(sprintf('Expected version: %s, actual version: %s', $this->renderExpectedVersion($expectedVersion), $this->renderExpectedVersion($actualVersion)), 1477143473);
    }

    private function renderExpectedVersion(int $expectedVersion)
    {
        if ($expectedVersion === ExpectedVersion::ANY) {
            return 'ANY (-2)';
        }
        if ($expectedVersion === ExpectedVersion::NO_STREAM) {
            return 'NO STREAM (-1)';
        }
        return (string)$expectedVersion;
    }
}
