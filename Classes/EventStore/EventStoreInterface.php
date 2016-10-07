<?php
namespace Neos\Cqrs\EventStore;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * EventStoreInterface
 */
interface EventStoreInterface
{
    /**
     * @param string $streamName
     * @return EventStream Can be empty stream
     */
    public function get(string $streamName): EventStream;

    /**
     * @param string $streamName
     * @return boolean
     */
    public function contains(string $streamName): bool;

    /**
     * @param string $streamName
     * @param EventStream $stream
     * @return int committed version number
     * @throws \Exception
     */
    public function commit(string $streamName, EventStream $stream): int;
}
