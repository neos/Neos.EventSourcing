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

use Doctrine\DBAL\Driver\Statement;
use Neos\Cqrs\EventStore\StoredEvent;

/**
 * Stream Iterator for the doctrine based EventStore
 */
final class DoctrineStreamIterator implements \Iterator
{

    /**
     * @var Statement
     */
    private $statement;

    /**
     * @var array|false
     */
    private $currentEventData;

    /**
     * @var int
     */
    private $currentId;

    /**
     * @param Statement $statement
     */
    public function __construct(Statement $statement)
    {
        $this->statement = $statement;

        $this->rewind();
    }

    /**
     * @return StoredEvent
     */
    public function current()
    {
        if ($this->currentEventData === false) {
            return null;
        }
        $payload = json_decode($this->currentEventData['payload'], true);
        $metadata = json_decode($this->currentEventData['metadata'], true);
        $recordedAt = new \DateTimeImmutable($this->currentEventData['recordedat']);
        return new StoredEvent(
            $this->currentEventData['id'],
            $this->currentEventData['type'],
            $payload,
            $metadata,
            (int)$this->currentEventData['version'],
            $recordedAt
        );
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->currentEventData = $this->statement->fetch();

        if ($this->currentEventData !== false) {
            $this->currentId = (integer)$this->currentEventData['id'];
        } else {
            $this->currentId = -1;
        }
    }

    /**
     * @return bool|int
     */
    public function key()
    {
        if ($this->currentId === -1) {
            return false;
        }

        return $this->currentId;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->currentEventData !== false;
    }

    /**
     * @return void
     */
    public function rewind()
    {
        //Only perform rewind if current item is not the first element
        if ($this->currentId === 0) {
            return;
        }
        $this->statement->execute();

        $this->currentEventData = null;
        $this->currentId = -1;

        $this->next();
    }
}
