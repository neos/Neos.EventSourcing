<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Event;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;

/**
 * Event Type Resolver
 *
 * @Flow\Scope("singleton")
 */
class EventTypeResolver
{
    /**
     * @var ObjectManagerInterface
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
     * Injecting via setter injection because this resolver must also work during compile time, when proxy classes are
     * not available.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Register event listeners based on annotations
     */
    public function initializeObject(): void
    {
        $this->mapping = self::eventTypeMapping($this->objectManager);
        $this->reversedMapping = array_flip($this->mapping);
    }

    /**
     * Return the event type for the given Event object
     *
     * @param DomainEventInterface $event
     * @return string
     */
    public function getEventType(DomainEventInterface $event): string
    {
        $className = TypeHandling::getTypeForValue($event);
        return $this->getEventTypeByClassName($className);
    }

    /**
     * Return the event type for the given Event classname
     *
     * @param string $className
     * @return string
     */
    public function getEventTypeByClassName(string $className): string
    {
        if (!isset($this->mapping[$className])) {
            throw new \InvalidArgumentException(sprintf('Event Type not found for class name "%s"', $className), 1476249954);
        }
        return $this->mapping[$className];
    }

    /**
     * Return the event short name for the given Event object
     *
     * @param DomainEventInterface $event
     * @return string
     */
    public function getEventShortType(DomainEventInterface $event): string
    {
        $type = explode(':', $this->getEventType($event));
        return end($type);
    }

    /**
     * Return the event short name for the given Event classname
     *
     * @param string $className
     * @return string
     */
    public function getEventShortTypeByClassName(string $className): string
    {
        $type = explode(':', $this->getEventTypeByClassName($className));
        return end($type);
    }

    /**
     * Return the event classname for the given event type
     *
     * @param string $eventType
     * @return string
     */
    public function getEventClassNameByType(string $eventType): string
    {
        return $this->reversedMapping[$eventType];
    }

    /**
     * Create mapping between Event class name and Event type
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    public static function eventTypeMapping(ObjectManagerInterface $objectManager): array
    {
        $buildEventType = function ($eventClassName) use ($objectManager) {
            $packageKey = $objectManager->getPackageKeyByObjectName($eventClassName);
            if ($packageKey === false) {
                throw new \RuntimeException(sprintf('Could not determine package key from object name "%s"', $eventClassName), 1478088597);
            }
            $shortEventClassName = (new \ReflectionClass($eventClassName))->getShortName();
            return $packageKey . ':' . $shortEventClassName;
        };
        $mapping = [];
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        foreach ($reflectionService->getAllImplementationClassNamesForInterface(DomainEventInterface::class) as $eventClassName) {
            if (is_subclass_of($eventClassName, ProvidesEventTypeInterface::class)) {
                /** @noinspection PhpUndefinedMethodInspection */
                $eventTypeIdentifier = $eventClassName::getEventType();
            } else {
                $eventTypeIdentifier = $buildEventType($eventClassName);
            }
            if (in_array($eventTypeIdentifier, $mapping, true)) {
                throw new \RuntimeException(sprintf('Duplicate event type "%s" mapped from "%s".', $eventTypeIdentifier, $eventClassName), 1474710799);
            }
            $mapping[$eventClassName] = $eventTypeIdentifier;
        }
        return $mapping;
    }
}
