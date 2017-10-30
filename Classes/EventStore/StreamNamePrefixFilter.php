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

    /**
     * StreamNamePrefixFilter constructor.
     *
     * @param string $streamNamePrefix
     * @throws Exception
     */
    public function __construct(string $streamNamePrefix)
    {
        $streamNamePrefix = trim($streamNamePrefix);
        if ($streamNamePrefix === '') {
            throw new Exception('Empty stream name prefix filter provided', 1506517687);
        }
        $this->streamNamePrefix = $streamNamePrefix;
    }

    /**
     * @return array
     */
    public function getFilterValues(): array
    {
        return [
            self::FILTER_STREAM_NAME_PREFIX => $this->streamNamePrefix,
        ];
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getFilterValue(string $name)
    {
        switch ($name) {
            case self::FILTER_STREAM_NAME_PREFIX:
                return $this->streamNamePrefix;
            break;
        }
    }
}
