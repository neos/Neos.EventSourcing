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

use Neos\Cqrs\Event\EventInterface;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Annotations as Flow;

/**
 * A base class for projectors
 *
 * Specialized projectors may extend this class in order to use the convenience methods included. Alternatively, they
 * can as well just implement the ProjectorInterface and refrain from extending this base class.
 *
 * @api
 */
abstract class AbstractBaseProjector implements ProjectorInterface
{

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     * @api
     */
    protected $systemLogger;

    /**
     * Concrete projectors may override this property for setting the class name of the Read Model to a non-conventional name
     *
     * @var string
     * @api
     */
    protected $readModelClassName;

    /**
     * Sets the properties in a Read Model with corresponding properties of an event according to the given map of
     * event and read model property names.
     *
     * If no property names are specified, this method will try to map all accessible properties of the event to
     * the same name properties in the read model:
     *
     * $this->mapEventToReadModel($someEvent, $someModel);
     *
     * If you want more control over the mapping, you can pass the property names in three different ways:
     *
     * 1. [ "eventPropertyName" => "readModelPropertyName", ... ]
     * 2. [ "propertyName", ...]
     *
     * In the second case "propertyName" will be used both for determining the event property as well as the read model property.
     * Combinations of both are also possible:
     *
     * 3. [ "eventSomeFoo" => "readModelSomeFoo", "bar", "baz", "eventSomeQuux" => "readModelSomeQuux" ]
     *
     * For use in the concrete projection.
     *
     * @param EventInterface $event An event
     * @param object $readModel A read model
     * @param array $propertyNamesMap Property names of the event (key) and of the read model (value). Alternatively just the property name as value.
     * @return void
     * @api
     */
    protected function mapEventToReadModel(EventInterface $event, $readModel, array $propertyNamesMap = [])
    {
        if ($propertyNamesMap === []) {
            $propertyNamesMap = ObjectAccess::getGettablePropertyNames($event);
        }

        foreach ($propertyNamesMap as $eventPropertyName => $readModelPropertyName) {
            if (is_numeric($eventPropertyName)) {
                $eventPropertyName = $readModelPropertyName;
            }
            if (ObjectAccess::isPropertyGettable($event, $eventPropertyName) && ObjectAccess::isPropertySettable($readModel, $readModelPropertyName)) {
                ObjectAccess::setProperty($readModel, $readModelPropertyName, ObjectAccess::getProperty($event, $eventPropertyName));
            }
        }
    }
}
