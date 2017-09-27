<?php
namespace Neos\EventSourcing\EventStore;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Exception;

/**
 * Stream name prefix filter
 */
class StreamNamePrefixFilter implements EventStreamFilterInterface
{
    /**
     * @var string
     */
    private $streamNamePrefix;

    public function __construct(string $streamNamePrefix)
    {
        $streamNamePrefix = trim($streamNamePrefix);
        if ($streamNamePrefix === '') {
            throw new Exception('Empty stream name prefix filter provided', 1506517687);
        }
        $this->streamNamePrefix = $streamNamePrefix;
    }

    public function getStreamName(): string
    {
        return '';
    }

    public function hasStreamName(): bool
    {
        return false;
    }

    public function getStreamNamePrefix(): string
    {
        return $this->streamNamePrefix;
    }

    public function hasStreamNamePrefix(): bool
    {
        return true;
    }

    /**
     * @return string[] in the format ['Bounded.Context:SomeEvent', 'Bounded.Context:SomeOtherEvent', ...]
     */
    public function getEventTypes(): array
    {
        return [];
    }

    public function hasEventTypes(): bool
    {
        return false;
    }

    public function getMinimumSequenceNumber(): int
    {
        return 0;
    }

    public function hasMinimumSequenceNumber(): bool
    {
        return false;
    }
}
