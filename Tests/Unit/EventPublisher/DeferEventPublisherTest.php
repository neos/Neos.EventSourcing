<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventPublisher;

use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventPublisher\DeferEventPublisher;
use Neos\EventSourcing\EventPublisher\EventPublisherInterface;
use Neos\Flow\Tests\UnitTestCase;

class DeferEventPublisherTest extends UnitTestCase
{

    /**
     * @test
     */
    public function withoutPendingEventsTheWrappedEventPublisherIsNotCalled(): void
    {
        $mockPublisher = self::getMockBuilder(EventPublisherInterface::class)->getMock();

        $publisher = DeferEventPublisher::forPublisher(
            $mockPublisher
        );

        $mockPublisher
            ->expects(self::never())
            ->method('publish');

        $publisher->publish(DomainEvents::createEmpty());
        $publisher->invoke();
    }

    /**
     * @test
     */
    public function pendingEventsAreClearedAfterInvoke(): void
    {
        $mockPublisher = self::getMockBuilder(EventPublisherInterface::class)->getMock();
        $eventA = self::getMockBuilder(DomainEventInterface::class)->getMock();
        $eventB = self::getMockBuilder(DomainEventInterface::class)->getMock();

        $publisher = DeferEventPublisher::forPublisher(
            $mockPublisher
        );

        $mockPublisher
            ->expects(self::exactly(2))
            ->method('publish')
            ->withConsecutive(
                [DomainEvents::withSingleEvent($eventA)],
                [DomainEvents::withSingleEvent($eventB)]
            );

        $publisher->publish(DomainEvents::withSingleEvent($eventA));
        $publisher->invoke();

        $publisher->publish(DomainEvents::withSingleEvent($eventB));
        $publisher->invoke();
    }

}
