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
 * Stream name and version filter
 *
 * This matches a single event in a stream
 */
class StreamNameAndVersionFilter implements EventStreamFilterInterface
{
    /**
     * @var string
     */
    private $streamName;

    /**
     * @var int
     */
    private $version;

    /**
     * StreamNameAndVersionFilter constructor.
     *
     * @param string $streamName
     * @param int $version
     * @throws Exception
     */
    public function __construct(string $streamName, int $version)
    {
        $streamName = trim($streamName);
        if ($streamName === '') {
            throw new Exception('Empty stream filter provided', 1513170391);
        }
        if ($version < 0) {
            throw new Exception('Negative version filter provided', 1513170397);
        }
        $this->streamName = $streamName;
        $this->version = $version;
    }

    /**
     * @return array
     */
    public function getFilterValues(): array
    {
        return [
            self::FILTER_STREAM_NAME => $this->streamName,
            self::FILTER_VERSION => $this->version,
        ];
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getFilterValue(string $name)
    {
        switch ($name) {
            case self::FILTER_STREAM_NAME:
                return $this->streamName;
            case self::FILTER_VERSION:
                return $this->version;
            break;
        }
    }
}
