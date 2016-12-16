<?php
namespace Neos\EventSourcing\ProcessManager;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\EventSourcing\Event\EventInterface;
use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\EventSourcing\EventListener\ActsBeforeInvokingEventListenerMethodsInterface;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\ProcessManager\State\ProcessState;
use Neos\EventSourcing\ProcessManager\State\StateRepository;
use Neos\Flow\Annotations as Flow;

/**
 * Base implementation for a process manager
 */
abstract class AbstractProcessManager implements EventListenerInterface, ActsBeforeInvokingEventListenerMethodsInterface
{
    /**
     * @Flow\Inject
     * @var StateRepository
     */
    protected $stateRepository;

    /**
     * @var ProcessState
     */
    protected $processState;

    /**
     * Return the process configuration for the concrete process manager
     *
     * This method must return an array which contains the event class names and a correlating closure which
     * returns a process state identifier. This process state identifier will be used throughout all event listener
     * methods in the process manager to always retrieve the correct state.
     *
     * The event class name is the fully qualified class name of the respective event. It is the same used in the
     * first type hint of the related event listener method.
     *
     * The following example uses the organization's identifier as the process state identifier:
     *
     *     return [
     *         OrganizationHasBeenCreated::class  => function(OrganizationHasBeenCreated $event) {
     *             return $event->getIdentifier();
     *         },
     *         MemberHasBeenAddedToOrganization::class => function(MemberHasBeenAddedToOrganization $event) {
     *             return $event->getOrganizationIdentifier();
     *         },
     *         NewDeploymentKeyHasBeenGenerated::class => function(NewDeploymentKeyHasBeenGenerated $event) {
     *             return $event->getOrganizationIdentifier();
     *         }
     *
     * @return array
     */
    abstract protected function getProcessConfiguration(): array;

    /**
     * Prepare the process state
     *
     * @param EventInterface $event
     * @param RawEvent $rawEvent
     * @throws ProcessManagerException
     */
    public function beforeInvokingEventListenerMethod(EventInterface $event, RawEvent $rawEvent)
    {
        $configuration = $this->getProcessConfiguration();
        $eventClassName = get_class($event);
        if (!isset($configuration[$eventClassName])) {
            throw new ProcessManagerException(sprintf('The event listener "%s" does not have a process configuration with key "%s". Check the array returned by getProcessConfiguration().', get_class($this), $eventClassName, get_class($this)), 1479290834150);
        }

        $stateIdentifier = $configuration[$eventClassName]($event);
        $this->processState = $this->stateRepository->get($stateIdentifier, get_class($this));
        if ($this->processState === null) {
            $this->processState = new ProcessState($stateIdentifier, get_class($this));
        }
    }
}
