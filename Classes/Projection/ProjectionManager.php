<?php
namespace Neos\Cqrs\Projection;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\EventTypeResolver;
use Neos\Cqrs\EventListener\EventListenerLocator;
use Neos\Cqrs\EventListener\AsynchronousEventListenerInterface;
use Neos\Cqrs\EventStore\EventStore;
use Neos\Cqrs\EventStore\EventTypesFilter;
use Neos\Cqrs\EventStore\Exception\EventStreamNotFoundException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Reflection\ClassReflection;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * Central authority for managing projections
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ProjectionManager
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var EventTypeResolver
     */
    protected $eventTypeResolver;

    /**
     * @Flow\Inject
     * @var EventListenerLocator
     */
    protected $eventListenerLocator;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $projectionCache;

    /**
     * @Flow\Inject
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var array in the format ['<projectionIdentifier>' => '<projectorClassName>', ...]
     */
    private $projections = [];

    /**
     * Register event listeners based on annotations
     */
    protected function initializeObject()
    {
        $this->projections = self::detectProjectors($this->objectManager);
    }

    /**
     * Return all detected projections
     *
     * @return Projection[]
     * @api
     */
    public function getProjections()
    {
        return array_map([$this, 'getProjection'], array_keys($this->projections));
    }

    /**
     * Returns information about a specific projection in form of a Projection DTO
     *
     * @param string $projectionIdentifier The short or full projection identifier
     * @return Projection
     * @api
     */
    public function getProjection(string $projectionIdentifier): Projection
    {
        $fullProjectionIdentifier = $this->normalizeProjectionIdentifier($projectionIdentifier);
        $projectorClassName = $this->projections[$fullProjectionIdentifier];
        $eventTypes = $this->eventListenerLocator->getEventTypesByListenerClassName($projectorClassName);
        return new Projection($fullProjectionIdentifier, $projectorClassName, $eventTypes);
    }

    /**
     * Tells if the specified projection is currently empty
     *
     * @param string $projectionIdentifier
     * @return bool
     */
    public function isProjectionEmpty(string $projectionIdentifier): bool
    {
        $fullProjectionIdentifier = $this->normalizeProjectionIdentifier($projectionIdentifier);
        $projectorClassName = $this->projections[$fullProjectionIdentifier];
        $projector = $this->objectManager->get($projectorClassName);
        return $projector->isEmpty();
    }

    /**
     * Replay events of the specified projection
     *
     * @param string $projectionIdentifier
     * @return int Number of events which have been replayed
     * @api
     */
    public function replay(string $projectionIdentifier): int
    {
        $eventCount = 0;
        $projection = $this->getProjection($projectionIdentifier);

        $projector = $this->objectManager->get($projection->getProjectorClassName());
        $projector->reset();

        $filter = new EventTypesFilter($projection->getEventTypes());

        try {
            $eventStream = $this->eventStore->get($filter);
        } catch (EventStreamNotFoundException $exception) {
            return 0;
        }
        foreach ($eventStream as $sequenceNumber => $eventAndRawEvent) {
            $rawEvent = $eventAndRawEvent->getRawEvent();
            $listener = $this->eventListenerLocator->getListener($rawEvent->getType(), $projection->getProjectorClassName());
            call_user_func($listener, $eventAndRawEvent->getEvent(), $rawEvent);
            $eventCount ++;

            if ($projector instanceof AsynchronousEventListenerInterface) {
                $projector->saveHighestAppliedSequenceNumber($sequenceNumber);
            }
        }
        return $eventCount;
    }

    /**
     * Play all events for the given projection which haven't been applied yet.
     *
     * @param string $projectionIdentifier
     * @return int Number of events which have been applied
     */
    public function catchUp(string $projectionIdentifier): int
    {
        $projection = $this->getProjection($projectionIdentifier);
        if (!$projection->isAsynchronous()) {
            throw new \InvalidArgumentException(sprintf('The projection "%s" is not asynchronous, so catching up is not supported.', $projection->getIdentifier()), 1479147244634);
        }

        /** @var AsynchronousEventListenerInterface $projector */
        $projector = $this->objectManager->get($projection->getProjectorClassName());
        $lastAppliedSequenceNumber = $projector->getHighestAppliedSequenceNumber();

        $filter = new EventTypesFilter($projection->getEventTypes(), $lastAppliedSequenceNumber + 1);
        $eventCount = 0;
        try {
            $eventStream = $this->eventStore->get($filter);
        } catch (EventStreamNotFoundException $exception) {
            return 0;
        }
        foreach ($eventStream as $sequenceNumber => $eventAndRawEvent) {
            $rawEvent = $eventAndRawEvent->getRawEvent();
            $listener = $this->eventListenerLocator->getListener($rawEvent->getType(), $projection->getProjectorClassName());
            call_user_func($listener, $eventAndRawEvent->getEvent(), $rawEvent);
            $eventCount ++;

            $projector->saveHighestAppliedSequenceNumber($sequenceNumber);
        }
        return $eventCount;
    }

    /**
     * Takes a short projection identifier and returns the "full" identifier if valid
     *
     * @param string $projectionIdentifier in the form "<package.key>:<projection>", "<key>:<projection>" or "<projection">"
     * @return string
     * @throws \InvalidArgumentException if no matching projector could be found
     */
    private function normalizeProjectionIdentifier($projectionIdentifier)
    {
        $matchingIdentifiers = [];
        foreach (array_keys($this->projections) as $fullProjectionIdentifier) {
            if ($this->projectionIdentifiersMatch($projectionIdentifier, $fullProjectionIdentifier)) {
                $matchingIdentifiers[] = $fullProjectionIdentifier;
            }
        }
        if ($matchingIdentifiers === []) {
            throw new \InvalidArgumentException(sprintf('No projection could be found that matches the projection identifier "%s"', $projectionIdentifier), 1476368605);
        }
        if (count($matchingIdentifiers) !== 1) {
            throw new \InvalidArgumentException(sprintf('More than one projection matches the projection identifier "%s":%s%s', $projectionIdentifier, chr(10), implode(', ', $matchingIdentifiers)), 1476368615);
        }
        return $matchingIdentifiers[0];
    }

    /**
     * Determines whether the given $shortIdentifier in the form "<package.key>:<projection>", "<key>:<projection>" or "<projection">"
     * matches the $fullIdentifier in the form "<package.key>:<projection>"
     *
     * @param string $shortIdentifier
     * @param string $fullIdentifier The full projection identifier
     * @return bool
     * @throws \InvalidArgumentException if the given $shortIdentifier is not in the valid form
     * @see normalizeProjectionIdentifier()
     */
    private function projectionIdentifiersMatch(string $shortIdentifier, string $fullIdentifier): bool
    {
        $shortIdentifier = strtolower($shortIdentifier);
        if ($shortIdentifier === $fullIdentifier) {
            return true;
        }
        $shortIdentifierParts = explode(':', $shortIdentifier);
        $fullIdentifierParts = explode(':', $fullIdentifier);

        $shortIdentifierPartsCount = count($shortIdentifierParts);
        if ($shortIdentifierPartsCount === 1) {
            return $shortIdentifier === $fullIdentifierParts[1];
        }
        if ($shortIdentifierPartsCount !== 2) {
            throw new \InvalidArgumentException(sprintf('Invalid projection identifier "%s", identifiers must have the format "<projection>" or "<package-key>:<projection>".', $shortIdentifier), 1476367741);
        }
        return
            $shortIdentifierParts[1] === $fullIdentifierParts[1]
            && substr($fullIdentifierParts[0], -(strlen($shortIdentifierParts[0]) + 1)) === '.' . $shortIdentifierParts[0];
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    protected static function detectProjectors($objectManager)
    {
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        /** @var PackageManagerInterface $packageManager */
        $packageManager = $objectManager->get(PackageManagerInterface::class);
        $projections = [];
        foreach ($reflectionService->getAllImplementationClassNamesForInterface(ProjectorInterface::class) as $projectorClassName) {
            $package = $packageManager->getPackageByClassName($projectorClassName);
            $projectionName = (new ClassReflection($projectorClassName))->getShortName();
            if (substr($projectionName, -9) === 'Projector') {
                $projectionName = substr($projectionName, 0, -9);
            }
            $projectionName = strtolower($projectionName);
            $packageKey = strtolower($package->getPackageKey());
            $projectionIdentifier = $packageKey . ':' . $projectionName;
            if (isset($projections[$projectionIdentifier])) {
                throw new \RuntimeException(sprintf('The projection identifier "%s" is ambiguous, please rename one of the classes "%s" or "%s"', $projectionIdentifier, $projections[$projectionIdentifier], $projectorClassName), 1476198478);
            }
            $projections[$projectionIdentifier] = $projectorClassName;
        }
        ksort($projections);
        return $projections;
    }
}
