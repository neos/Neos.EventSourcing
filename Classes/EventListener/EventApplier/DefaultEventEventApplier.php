<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener\EventApplier;

use Neos\EventSourcing\EventListener\AfterInvokeInterface;
use Neos\EventSourcing\EventListener\BeforeInvokeInterface;
use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\EventSourcing\EventListener\Exception\EventCouldNotBeAppliedException;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class DefaultEventEventApplier implements EventApplierInterface
{
    private function __construct(
        private EventListenerInterface $eventListener,
    ) {
    }

    public static function forEventListener(EventListenerInterface $eventListener): self
    {
        return new self($eventListener);
    }

    /**
     * @param EventEnvelope $eventEnvelope
     * @throws EventCouldNotBeAppliedException
     */
    public function __invoke(EventEnvelope $eventEnvelope): void
    {
        $event = $eventEnvelope->getDomainEvent();
        $rawEvent = $eventEnvelope->getRawEvent();
        try {
            $listenerMethodName = 'when' . (new \ReflectionClass($event))->getShortName();
        } catch (\ReflectionException $exception) {
            throw new \RuntimeException(sprintf('Could not extract listener method name for listener %s and event %s', \get_class($this->eventListener), \get_class($event)), 1541003718, $exception);
        }
        if (!method_exists($this->eventListener, $listenerMethodName)) {
            return;
        }
        if ($this->eventListener instanceof BeforeInvokeInterface) {
            $this->eventListener->beforeInvoke($eventEnvelope);
        }
        try {
            $this->eventListener->$listenerMethodName($event, $rawEvent);
        } catch (\Throwable $exception) {
            throw new EventCouldNotBeAppliedException(sprintf('Event "%s" (%s) could not be applied to %s. Sequence number (%d) is not updated', $rawEvent->getIdentifier(), $rawEvent->getType(), \get_class($this->eventListener), $rawEvent->getSequenceNumber()), 1544207001, $exception, $eventEnvelope, $this->eventListener);
        }
        if ($this->eventListener instanceof AfterInvokeInterface) {
            $this->eventListener->afterInvoke($eventEnvelope);
        }
    }
}
