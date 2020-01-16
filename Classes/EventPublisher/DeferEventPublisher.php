<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventPublisher;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DomainEvents;

/**
 * TODO
 */
final class DeferEventPublisher implements EventPublisherInterface
{
    /**
     * @var DomainEvents
     */
    private $pendingEvents;

    /**
     * @var EventPublisherInterface
     */
    private $wrappedEventPublisher;

    protected function __construct(EventPublisherInterface $wrappedEventPublisher)
    {
        $this->wrappedEventPublisher = $wrappedEventPublisher;
        $this->pendingEvents = DomainEvents::createEmpty();
    }

    public static function forPublisher(EventPublisherInterface $eventPublisher): self
    {
        return new static($eventPublisher);
    }

    /**
     * @param DomainEvents $events
     */
    public function publish(DomainEvents $events): void
    {
        $this->pendingEvents = $this->pendingEvents->appendEvents($events);
    }

    public function getWrappedEventPublisher(): EventPublisherInterface
    {
        return $this->wrappedEventPublisher;
    }

    /**
     * TODO
     */
    public function invoke(): void
    {
        if (!$this->pendingEvents->isEmpty()) {
            $this->wrappedEventPublisher->publish($this->pendingEvents);
        }
    }

    public function shutdownObject(): void
    {
        $this->invoke();
    }
}
