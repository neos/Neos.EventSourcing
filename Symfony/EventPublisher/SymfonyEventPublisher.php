<?php


namespace Neos\EventSourcing\Symfony\EventPublisher;


use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventPublisher\EventPublisherInterface;
use Neos\EventSourcing\EventPublisher\JobQueue\CatchUpEventListenerJob;
use Neos\EventSourcing\Symfony\Command\InternalCatchUpEventListenerCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;

class SymfonyEventPublisher implements EventPublisherInterface
{

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    private $eventStoreContainerId;

    public function __construct(EventDispatcherInterface $eventDispatcher, string $eventStoreContainerId)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->eventStoreContainerId = $eventStoreContainerId;
    }


    public function publish(DomainEvents $events): void
    {
        // TODO: listener filter

        $queuedEventListenerClassNames = [];
        $processedEventClassNames = [];
        foreach ($events as $event) {
            /** @var string $eventClassName */
            $eventClassName = \get_class($event instanceof DecoratedEvent ? $event->getWrappedEvent() : $event);

            // only process every Event type once
            if (isset($processedEventClassNames[$eventClassName])) {
                continue;
            }


            // NOTE: eventDispatcher is ONLY used for resolving the class name of the listener.
            // you are NEVER allowed to call $this->eventDispatcher->dispatch($event, $eventClassName);
            // because otherwise, the appliedEventsLog is not updated properly.
            $listeners = $this->eventDispatcher->getListeners($eventClassName);
            dump($listeners);

            foreach ($listeners as $listenerClassNameAndMethodName) {
                $listenerClassName = get_class($listenerClassNameAndMethodName[0]);

                // only process every Event Listener once
                if (isset($queuedEventListenerClassNames[$listenerClassName])) {
                    continue;
                }


                $this->triggerAsyncBackgroundJob($listenerClassName);

                $queuedEventListenerClassNames[$listenerClassName] = true;
            }


            // we have to know: "what event listers do we need to trigger"?
            /*foreach ($this->mappings as $mapping) {
                if ($mapping->getEventClassName() !== $eventClassName) {
                    continue;
                }
                // only process every Event Listener once
                if (isset($queuedEventListenerClassNames[$mapping->getListenerClassName()])) {
                    continue;
                }
                $queueName = $mapping->getOption('queueName', self::DEFAULT_QUEUE_NAME);
                $options = $mapping->getOption('queueOptions', []);
                $this->jobManager->queue($queueName, new CatchUpEventListenerJob($mapping->getListenerClassName(), $this->eventStoreIdentifier), $options);
                $queuedEventListenerClassNames[$mapping->getListenerClassName()] = true;
            }*/
        }

    }

    private function triggerAsyncBackgroundJob($listenerClassName)
    {
        $process = new Process(['php', 'bin/console', InternalCatchUpEventListenerCommand::getDefaultName(), $listenerClassName, $this->eventStoreContainerId]);
        $process->run(); // !! WE DO NOT WAIT FOR THE RESULT - start !! - TODO: adjust
        $errOut = $process->getOutput() . $process->getErrorOutput();
        dump($errOut);
    }
}