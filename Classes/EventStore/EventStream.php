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

use Neos\Cqrs\Event\EventMetadata;
use Neos\Cqrs\Event\EventWithMetadata;
use Neos\Cqrs\Event\EventTypeResolver;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;

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
     * @var integer
     */
    protected $version;

    /**
     * @param \Iterator $streamIterator
     * @param integer $version
     */
    public function __construct(\Iterator $streamIterator, int $version = -1)
    {
        $this->streamIterator = $streamIterator;
        $this->version = $version;
    }

    /**
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return EventWithMetadata
     */
    public function current()
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->allowAllProperties();
        $configuration->forProperty('*')->allowAllProperties();

        /** @var EventFromStream $eventFromStream */
        $eventFromStream = $this->streamIterator->current();
        $eventClassName = $this->eventTypeResolver->getEventClassNameByType($eventFromStream->getType());
        return new EventWithMetadata(
            $this->propertyMapper->convert($eventFromStream->getPayload(), $eventClassName, $configuration),
            $this->propertyMapper->convert($eventFromStream->getMetadata(), EventMetadata::class, $configuration)
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