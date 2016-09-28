<?php
namespace Neos\Cqrs\Event;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Exception;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Utility\TypeHandling;

/**
 * EventTypeService
 *
 * @Flow\Scope("singleton")
 */
class EventTypeService
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $mapping = [];

    /**
     * @var array
     */
    protected $reversedMapping = [];

    /**
     * Register event listeners based on annotations
     */
    public function initializeObject()
    {
        $this->mapping = self::eventTypeMapping($this->objectManager);
        $this->reversedMapping = array_flip($this->mapping);
    }

    /**
     * Return the event type for the given Event object
     *
     * @param EventInterface $event
     * @return string
     */
    public function getEventType(EventInterface $event): string
    {
        $classname = TypeHandling::getTypeForValue($event);
        return $this->mapping[$classname];
    }

    /**
     * Return the event type for the given Event classname
     *
     * @param string $classname
     * @return string
     */
    public function getEventTypeByImplementation(string $classname): string
    {
        return $this->mapping[$classname];
    }

    /**
     * Return the event short name for the given Event object
     *
     * @param EventInterface $event
     * @return string
     */
    public function getEventShortType(EventInterface $event): string
    {
        $type = explode(':', $this->getEventType($event));
        return end($type);
    }

    /**
     * Return the event short name for the given Event classname
     *
     * @param string $classname
     * @return string
     */
    public function getEventShortTypeByImplementation(string $classname): string
    {
        $type = explode(':', $this->getEventTypeByImplementation($classname));
        return end($type);
    }

    /**
     * Return the event classname for the given event type
     *
     * @param $eventType
     * @return string
     */
    public function getEventImplementation($eventType):string
    {
        return $this->reversedMapping[$eventType];
    }

    /**
     * Create mapping between Event classname and Event type
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @throws Exception
     * @Flow\CompileStatic
     */
    public static function eventTypeMapping(ObjectManagerInterface $objectManager)
    {
        $buildEventType = function ($eventClassname) {
            list($vendor, $package) = explode('\\', $eventClassname);
            $eventName = substr($eventClassname, strrpos($eventClassname, '\\') + 1);
            return $vendor . ':' . $package . ':' . $eventName;
        };
        $mapping = [];
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        foreach ($reflectionService->getAllImplementationClassNamesForInterface(EventInterface::class) as $eventClassname) {
            $type = $buildEventType($eventClassname);
            if (in_array($type, $mapping)) {
                throw new Exception(sprintf('Duplicate event type "%s"', $type), 1474710799);
            }
            $mapping[$eventClassname] = $buildEventType($eventClassname);
        }
        return $mapping;
    }
}
