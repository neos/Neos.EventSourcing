<?php
namespace Neos\Cqrs\TypeConverter;

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
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Flow\Reflection\ObjectAccess;
use Neos\Flow\Utility\TypeHandling;

/**
 * Simple TypeConverter that can turn instances of EventInterface to an array that contains all properties recursively
 */
class EventToArrayConverter extends AbstractTypeConverter
{

    /**
     * @var array<string>
     */
    protected $sourceTypes = [EventInterface::class];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array|\Neos\Flow\Error\Error
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        return $this->convertObject($source);
    }

    /**
     * @param mixed $object
     * @return array
     */
    protected function convertObject($object)
    {
        $properties = ObjectAccess::getGettableProperties($object);
        foreach ($properties as $propertyName => &$propertyValue) {
            if (TypeHandling::isSimpleType(gettype($propertyValue))) {
                continue;
            }
            if ($propertyValue instanceof \DateTimeZone) {
                $propertyValue = $propertyValue->getName();
            } elseif ($propertyValue instanceof \DateTimeInterface) {
                $propertyValue = $propertyValue->format(DATE_ISO8601);
            } elseif (is_object($propertyValue)) {
                $propertyValue = $this->convertObject($propertyValue);
            }
        }
        return $properties;
    }
}
