<?php
namespace Flowpack\Cqrs\EventStore;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * EventStoreInterface
 */
interface EventStoreInterface
{
    /**
     * Get events for AR
     * @param  string $identifier
     * @return EventStream Can be empty stream
     */
    public function get(string $identifier): EventStream;

    /**
     * Persist new AR events
     * @param  EventStream $stream
     * @return void
     * @throws \Exception
     */
    public function commit(EventStream $stream);
}
