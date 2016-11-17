<?php
namespace Neos\Cqrs\ProcessManager;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Cqrs\Event\EventInterface;
use Neos\Cqrs\EventListener\EventListenerInterface;
use Neos\Cqrs\EventListener\ActsBeforeInvokingEventListenerMethodsInterface;
use Neos\Cqrs\EventStore\RawEvent;
use Neos\Cqrs\ProcessManager\State\State;
use Neos\Cqrs\ProcessManager\State\StateRepository;
use TYPO3\Flow\Annotations as Flow;

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
     * @var State
     */
    protected $state;

    /**
     * Return the process configuration for the concrete process manager
     *
     * This method must return an array which contains (short) event names and a correlating closure which
     * returns a process state identifier. This process state identifier will be used throughout all event listener
     * methods in the process manager to always retrieve the correct state.
     *
     * The "short event name" is the same used in the "when<ShortName>()" method name of the event listener method.
     *
     * The following example uses the organization's identifier as the process state identifier:
     *
     *     return [
     *         'OrganizationHasBeenCreated' => function(OrganizationHasBeenCreated $event) {
     *             return $event->getIdentifier();
     *         },
     *         'MemberHasBeenAddedToOrganization' => function(MemberHasBeenAddedToOrganization $event) {
     *             return $event->getOrganizationIdentifier();
     *         },
     *         'NewDeploymentKeyHasBeenGenerated' => function(NewDeploymentKeyHasBeenGenerated $event) {
     *             return $event->getOrganizationIdentifier();
     *         }
     *
     * @return array
     */
    abstract protected function getProcessConfiguration(): array;

    /**
     * Prepare the process state
     *
     * @param string $eventListenerMethodName
     * @param EventInterface $event
     * @param RawEvent $rawEvent
     * @throws ProcessManagerException
     */
    public function beforeInvokingEventListenerMethod(string $eventListenerMethodName, EventInterface $event, RawEvent $rawEvent)
    {
        $configuration = $this->getProcessConfiguration();
        $eventName = substr($eventListenerMethodName, 4);
        if (!isset($configuration[$eventName])) {
            throw new ProcessManagerException(sprintf('The event listener method "%s" does not have a matching process configuration with key "%s". Check the array returned by getProcessConfiguration() in class %s.', $eventListenerMethodName, $eventName, get_class($this)), 1479290834150);
        }

        $stateIdentifier = $configuration[$eventName]($event);
        $this->state = $this->stateRepository->get($stateIdentifier, get_class($this));
        if ($this->state === null) {
            $this->state = new State($stateIdentifier, get_class($this));
        }
    }
}
