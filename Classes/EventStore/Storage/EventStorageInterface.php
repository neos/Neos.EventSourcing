<?php
namespace Neos\Cqrs\EventStore\Storage;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\EventStore\EventStream;
use Neos\Cqrs\EventStore\ExpectedVersion;
use Neos\Cqrs\EventStore\WritableEvents;

/**
 * EventStorageInterface
 */
interface EventStorageInterface
{
    public function load(string $streamName): EventStream;

    public function commit(string $streamName, WritableEvents $events, int $expectedVersion = ExpectedVersion::ANY);
}
