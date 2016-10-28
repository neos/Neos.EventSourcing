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
use Neos\Cqrs\EventStore\EventStore;
use Neos\Cqrs\EventStore\EventStreamFilterInterface;
use Neos\Cqrs\EventStore\EventTypesFilter;
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
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var EventTypeResolver
     */
    protected $eventTypeResolver;

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
     * @var Projection[]
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
     * @return Projection[]
     */
    public function getProjections()
    {
        return $this->projections;
    }

    /**
     * @param string $projectionIdentifier
     * @return void
     */
    public function replay(string $projectionIdentifier)
    {
        $eventTypes = [];
        $fullProjectionIdentifier = $this->normalizeProjectionIdentifier($projectionIdentifier);
        $projection = $this->projections[$fullProjectionIdentifier];
        $projectorClassName = $projection->getProjectorClassName();
        foreach (get_class_methods($projectorClassName) as $methodName) {
            preg_match('/^when.*$/', $methodName, $matches);
            if (!isset($matches[0])) {
                continue;
            }
            $parameters = array_values($this->reflectionService->getMethodParameters($projectorClassName, $methodName));
            $eventTypes[] = $this->eventTypeResolver->getEventTypeByClassName($parameters[0]['class']);
        }
        $filter = new EventTypesFilter($eventTypes);

        $projector = $this->objectManager->get($projectorClassName);
        foreach ($this->eventStore->get($filter) as $eventWithMetadata) {
            $listenerMethodName = 'when' . $this->eventTypeResolver->getEventShortType($eventWithMetadata->getEvent());
            call_user_func([$projector, $listenerMethodName], $eventWithMetadata->getEvent(), $eventWithMetadata->getMetadata());
        }
    }

    /**
     * @param string $projectionIdentifier
     * @return void
     */
    public function catchup(string $projectionIdentifier)
    {
        $fullProjectionIdentifier = $this->normalizeProjectionIdentifier($projectionIdentifier);
        $cacheId = md5($fullProjectionIdentifier);
        if (!$this->projectionCache->has($cacheId)) {
            $projectionState = [
                'revision' => 0
            ];
        } else {
            $projectionState = $this->projectionCache->get($cacheId);
            $projectionState['revision'] ++;
        }
        $this->projectionCache->set($cacheId, $projectionState);
        \TYPO3\Flow\var_dump($projectionState);

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
                $matchingIdentifiers[] = $fullProjectionIdentifier;;
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
            $projectionIdentifier = strtolower($package->getPackageKey() . ':' . (new ClassReflection($projectorClassName))->getShortName());
            if (isset($projections[$projectionIdentifier])) {
                throw new \RuntimeException(sprintf('The projection identifier "%s" is ambiguous, please rename one of the classes "%s" or "%s"', $projectionIdentifier, $projections[$projectionIdentifier], $projectorClassName), 1476198478);
            }
            $projections[$projectionIdentifier] = new Projection($projectionIdentifier, 'short', $package->getPackageKey(), $projectorClassName);
        }
        ksort($projections);
        return $projections;
    }

}