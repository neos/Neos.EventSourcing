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
        $this->reversedMapping = array_reverse($this->mapping);
    }

    /**
     * @param EventInterface $event
     * @return string
     */
    public function getEventType(EventInterface $event): string
    {
        $classname = TypeHandling::getTypeForValue($event);
        return $this->mapping[$classname];
    }

    /**
     * @param EventInterface $event
     * @return string
     */
    public function getEventShortType(EventInterface $event): string
    {
        $type = explode(':', $this->getEventType($event));
        return end($type);
    }

    /**
     * @param $eventType
     * @return string
     */
    public function getEventTypeImplementation($eventType):string
    {
        return $this->reversedMapping[$eventType];
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return array
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
