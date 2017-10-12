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
 * Stream name filter (exact name)
 */
class StreamNameFilter implements EventStreamFilterInterface
{
    /**
     * @var string
     */
    private $streamName;

    public function __construct(string $streamName)
    {
        $streamName = trim($streamName);
        if ($streamName === '') {
            throw new Exception('Empty stream filter provided', 1478299970);
        }
        $this->streamName = $streamName;
    }

    /**
     * @return array
     */
    public function getFilterValues(): array
    {
        return [
            self::FILTER_STREAM_NAME => $this->streamName,
        ];
    }
}
