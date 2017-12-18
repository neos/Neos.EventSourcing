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
 * An EventStream filter matching single events by their identifier
 */
class EventIdentifierFilter implements EventStreamFilterInterface
{
    /**
     * @var string
     */
    private $eventIdentifier;

    /**
     * EventIdentifierFilter constructor.
     *
     * @param string $eventIdentifier
     * @throws Exception
     */
    public function __construct(string $eventIdentifier)
    {
        if (empty($eventIdentifier)) {
            throw new Exception('No event identifier filter specified', 1513329147);
        }
        $this->eventIdentifier = $eventIdentifier;
    }

    /**
     * @return array
     */
    public function getFilterValues(): array
    {
        return [
            self::FILTER_EVENT_IDENTIFIER => $this->eventIdentifier,
        ];
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getFilterValue(string $name)
    {
        switch ($name) {
            case self::FILTER_EVENT_IDENTIFIER:
                return $this->eventIdentifier;
            break;
        }
    }
}
