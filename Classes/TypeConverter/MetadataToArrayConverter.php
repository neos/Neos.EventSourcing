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

use Neos\Cqrs\Event\EventMetadata;
use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Utility\TypeHandling;

/**
 * Simple TypeConverter that can turn instances of EventMetadata to an array that contains all properties recursively
 */
class MetadataToArrayConverter extends EventToArrayConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = [EventMetadata::class];

    /**
     * @param EventMetadata $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array|Error
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if (!$source instanceof EventMetadata) {
            throw new \InvalidArgumentException('This converter only supports EventMetadata sources', 1475843240);
        }
        $convertedProperties = [];
        foreach ($source->getProperties() as $propertyName => $propertyValue) {
            $convertedProperties[$propertyName] = TypeHandling::isSimpleType(gettype($propertyValue)) ? $propertyValue : $this->convertObject($propertyValue);
        }
        return ['properties' => $convertedProperties];
    }
}
