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
use Neos\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\TypeHandling;

/**
 * Simple TypeConverter that can turn instances of MessageMetadata to an array that contains all properties recursively
 */
class MetadataToArrayConverter extends EventToArrayConverter
{

    /**
     * @var array<string>
     */
    protected $sourceTypes = array(MessageMetadata::class);

    /**
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array|\TYPO3\Flow\Error\Error
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        if (!$source instanceof MessageMetadata) {
            throw new \InvalidArgumentException('This converter only supports MessageMetadata sources', 1475843240);
        }
        $result = [];
        foreach ($source->getProperties() as $propertyName => $propertyValue) {
            $result[$propertyName] = TypeHandling::isSimpleType(gettype($propertyValue)) ? $propertyValue : $this->convertObject($propertyValue);
        }
        return $result;
    }
}