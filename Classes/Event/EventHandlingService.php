<?php
namespace Neos\Cqrs\Event;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventStore\Event\Metadata;
use Neos\EventStore\EventStore;
use Neos\EventStore\EventStream;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventTypeService
 *
 * @Flow\Scope("singleton")
 */
class EventHandlingService
{
    /**
     * @var EventStore
     * @Flow\Inject
     */
    protected $eventStore;

    /**
     * @var EventBus
     * @Flow\Inject
     */
    protected $eventBus;

    /**
     * Publish the given EventStream
     *
     * @param string $streamName name of the stream in the event store
     * @param EventStream $stream stream of event to store
     * @return int commited version number
     * @return int
     */
    public function publish(string $streamName, EventStream $stream) :int
    {
        return $this->eventStore->commit($streamName, $stream, function (EventTransport $eventTransport, int $version) {
            $eventTransport->getMetaData()->add(Metadata::VERSION, $version);
            $this->eventBus->handle($eventTransport);
        });
    }
}
