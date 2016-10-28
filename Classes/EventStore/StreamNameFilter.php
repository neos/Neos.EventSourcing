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

use TYPO3\Flow\Annotations as Flow;

class StreamNameFilter implements EventStreamFilterInterface
{
    /**
     * @var string
     */
    private $streamName;

    public function __construct(string $streamName)
    {
        $this->streamName = $streamName;
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    public function hasStreamName(): bool
    {
        return true;
    }

    public function getStreamNamePrefix(): string
    {
        return '';
    }

    public function hasStreamNamePrefix(): bool
    {
        return false;
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
}
