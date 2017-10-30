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

class EventTypesFilter implements EventStreamFilterInterface
{
    /**
     * @var string[]
     */
    private $eventTypes;

    /**
     * @var int
     */
    private $minimumSequenceNumber = 0;

    /**
     * EventTypesFilter constructor.
     *
     * @param array $eventTypes
     * @param int $minimumSequenceNumber
     * @throws Exception
     */
    public function __construct(array $eventTypes, int $minimumSequenceNumber = 0)
    {
        if ($eventTypes === []) {
            throw new Exception('No type filter provided', 1478299912);
        }
        $this->eventTypes = $eventTypes;
        $this->minimumSequenceNumber = $minimumSequenceNumber;
    }

    /**
     * @return array
     */
    public function getFilterValues(): array
    {
        return [
            self::FILTER_EVENT_TYPES => $this->eventTypes,
            self::FILTER_MINIMUM_SEQUENCE_NUMBER => $this->minimumSequenceNumber,
        ];
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getFilterValue(string $name)
    {
        switch ($name) {
            case self::FILTER_EVENT_TYPES:
                return $this->eventTypes;
            break;
            case self::FILTER_MINIMUM_SEQUENCE_NUMBER:
                return $this->minimumSequenceNumber;
            break;
        }
    }
}
