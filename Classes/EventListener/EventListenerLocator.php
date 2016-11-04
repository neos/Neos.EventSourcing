<?php
namespace Neos\Cqrs\EventListener;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\EventInterface;
use Neos\Cqrs\Event\EventMetadata;
use Neos\Cqrs\Event\EventTypeResolver;
use Neos\Cqrs\Exception;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * EventListenerLocator
 *
 * @Flow\Scope("singleton")
 */
class EventListenerLocator
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
    protected $eventTypeService;

    /**
     * @var array in the format ['<eventType>' => ['<listenerClassName>' => '<listenerMethodName>', '<listenerClassName2>' => '<listenerMethodName2>', ...]]
     */
    protected $mapping = [];

    /**
     * Register event listeners based on annotations
     */
    public function initializeObject()
    {
        $this->mapping = self::detectListeners($this->objectManager);
    }

    /**
     * Returns event listeners for the given event (type)
     *
     * @param EventInterface $event
     * @return \callable[]
     */
    public function getListeners(EventInterface $event): array
    {
        $eventType = $this->eventTypeService->getEventType($event);
        if (!isset($this->mapping[$eventType])) {
            return [];
        }
        $listeners = [];
        array_walk($this->mapping[$eventType], function ($listenerMethodName, $listenerClassName) use (&$listeners) {
            $listeners[] = [$this->objectManager->get($listenerClassName), $listenerMethodName];
        });
        return $listeners;
    }

    /**
     * @param EventInterface $event
     * @param string $listenerClassName
     * @return \callable|null
     */
    public function getListener(EventInterface $event, string $listenerClassName)
    {
        $eventType = $this->eventTypeService->getEventType($event);
        if (!isset($this->mapping[$eventType][$listenerClassName])) {
            return null;
        }
        return [$this->objectManager->get($listenerClassName), $this->mapping[$eventType][$listenerClassName]];
    }

    /**
     * @param string $listenerClassName
     * @return string[]
     */
    public function getEventTypesByListenerClassName(string $listenerClassName): array
    {
        $eventTypes = [];
        array_walk($this->mapping, function ($listenerMappings, $eventType) use (&$eventTypes, $listenerClassName) {
            foreach (array_keys($listenerMappings) as $listenerMappingClassname) {
                if ($listenerMappingClassname === $listenerClassName) {
                    $eventTypes[] = $eventType;
                }
            }
        });
        return $eventTypes;
    }

    /**
     * Detects and collects all existing event listener classes
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @throws Exception
     * @Flow\CompileStatic
     */
    protected static function detectListeners(ObjectManagerInterface $objectManager): array
    {
        $listeners = [];
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        /** @var EventTypeResolver $eventTypeResolver */
        $eventTypeResolver = $objectManager->get(EventTypeResolver::class);
        foreach ($reflectionService->getAllImplementationClassNamesForInterface(EventListenerInterface::class) as $listenerClassName) {
            foreach (get_class_methods($listenerClassName) as $listenerMethodName) {
                preg_match('/^when[A-Z].*$/', $listenerMethodName, $matches);
                if (!isset($matches[0])) {
                    continue;
                }
                $parameters = array_values($reflectionService->getMethodParameters($listenerClassName, $listenerMethodName));

                if (!isset($parameters[0])) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, must accept an EventInterface and optionally a EventMetadata', $listenerClassName, $listenerMethodName), 1472500228);
                }
                $eventClassName = $parameters[0]['class'];
                if (!$reflectionService->isClassImplementationOf($eventClassName, EventInterface::class)) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the first parameter should be cast to an implementation of EventInterface', $listenerClassName, $listenerMethodName), 1472504443);
                }

                if (isset($parameters[1])) {
                    $metaDataType = $parameters[1]['class'];
                    if ($metaDataType !== EventMetadata::class) {
                        throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the second parameter should be cast to EventMetadata', $listenerClassName, $listenerMethodName), 1472504303);
                    }
                }
                $expectedMethodName = 'when' . (new \ReflectionClass($eventClassName))->getShortName();
                if ($expectedMethodName !== $listenerMethodName) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method name is expected to be "%s"', $listenerClassName, $listenerMethodName, $expectedMethodName), 1476442394);
                }

                $eventType = $eventTypeResolver->getEventTypeByClassName($eventClassName);
                if (!isset($listeners[$eventType])) {
                    $listeners[$eventType] = [];
                }
                $listeners[$eventType][$listenerClassName] = $listenerMethodName;
            }
        }

        ksort($listeners);
        return $listeners;
    }
}
