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
use Neos\Cqrs\Event\EventType;
use Neos\Cqrs\Exception;
use Neos\Cqrs\Message\MessageMetadata;
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
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $map = [];

    /**
     * Register event listeners based on annotations
     */
    public function initializeObject()
    {
        $this->map = self::detectListeners($this->objectManager);
    }

    /**
     * Returns event listeners for the given event (type)
     *
     * @param EventInterface $event
     * @return \Generator
     */
    public function getListeners(EventInterface $event): \Generator
    {
        $eventType = EventType::get($event);
        if (isset($this->map[$eventType])) {
            foreach ($this->map[$eventType] as $listener) {
                yield new EventListenerContainer($listener, $this->objectManager);
            }
        }
    }

    /**
     * Detects and collects all existing event listener classes
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @throws Exception
     * @Flow\CompileStatic
     * @todo be sure that projector are called after all the other event listeners !!!
     */
    protected static function detectListeners(ObjectManagerInterface $objectManager): array
    {
        $listeners = [];
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        foreach ($reflectionService->getAllImplementationClassNamesForInterface(EventListenerInterface::class) as $listenerClassName) {
            foreach (get_class_methods($listenerClassName) as $methodName) {
                preg_match('/^when.*$/', $methodName, $matches);
                if (!isset($matches[0])) {
                    continue;
                }

                $methodName = $matches[0];
                $parameters = array_values($reflectionService->getMethodParameters($listenerClassName, $methodName));
                $eventType = null;

                switch (true) {
                    case count($parameters) === 1:
                        $eventType = $parameters[0]['class'];
                        if (!$reflectionService->isClassImplementationOf($eventType, EventInterface::class)) {
                            throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the first parameter should by cast to an implementation of EventInterface', $listenerClassName, $methodName), 1472504443);
                        }
                        $metaDataType = null;
                        break;
                    case count($parameters) > 1:
                        $eventType = $parameters[0]['class'];
                        $metaDataType = $parameters[1]['class'];
                        if ($metaDataType !== MessageMetadata::class) {
                            throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the second parameter should by cast to MessageMetaData', $listenerClassName, $methodName), 1472504303);
                        }
                        break;
                }

                if (trim($eventType) === '') {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, must accept an EventInterface and optionally a MessageMetaData', $listenerClassName, $methodName), 1472500228);
                }

                $eventTypeParts = explode('\\', $eventType);
                $expectedMethod = 'when' . end($eventTypeParts);

                if ($expectedMethod !== $methodName) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method name is wrong, must be "%s"', $listenerClassName, $methodName, $expectedMethod), 1474298595796);
                }

                if (!isset($listeners[$eventType])) {
                    $listeners[$eventType] = [];
                }
                $listeners[$eventType][] = [$listenerClassName, $methodName];
            }
        }

        ksort($listeners);
        return $listeners;
    }
}
