<?php
namespace Flowpack\Cqrs\EventStore;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Domain\Uuid;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventStorageInterface
 */
interface EventStorageInterface
{
    /**
     * @param  Uuid $aggregateRootId
     * @return EventStreamData Aggregate Root events
     */
    public function load(Uuid $aggregateRootId);

    /**
     * @param  Uuid $aggregateRootId
     * @return integer Current Aggregate Root version
     */
    public function getCurrentVersion(Uuid $aggregateRootId);

    /**
     * @param  Uuid $aggregateRootId
     * @param  string $aggregateName
     * @param  string $data
     * @param  integer $version
     * @return void
     */
    public function write(Uuid $aggregateRootId, $aggregateName, $data, $version);
}
