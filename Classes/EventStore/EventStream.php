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

use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;

/**
 * EventStream
 */
final class EventStream implements \Iterator
{
    /**
     * @Flow\Inject
     * @var EventTypeResolver
     */
    protected $eventTypeResolver;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @var \Iterator
     */
    private $streamIterator;

    /**
     * @param \Iterator $streamIterator
     */
    public function __construct(\Iterator $streamIterator)
    {
        $this->streamIterator = $streamIterator;
    }

    /**
     * @return EventAndRawEvent
     */
    public function current()
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->allowAllProperties();
        $configuration->skipUnknownProperties();
        $configuration->forProperty('*')->allowAllProperties()->skipUnknownProperties();

        /** @var RawEvent $rawEvent */
        $rawEvent = $this->streamIterator->current();
        $eventClassName = $this->eventTypeResolver->getEventClassNameByType($rawEvent->getType());
        return new EventAndRawEvent(
            $this->propertyMapper->convert($rawEvent->getPayload(), $eventClassName, $configuration),
            $rawEvent
        );
    }

    public function next()
    {
        $this->streamIterator->next();
    }

    public function key()
    {
        return $this->streamIterator->key();
    }

    public function valid()
    {
        return $this->streamIterator->valid();
    }

    public function rewind()
    {
        $this->streamIterator->rewind();
    }
}
