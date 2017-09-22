<?php
namespace Neos\EventSourcing\Property;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Property\PropertyMappingConfiguration;

/**
 * A property mapping configuration that allows all property recursively and skips unknown properties.
 */
class AllowAllPropertiesPropertyMappingConfiguration extends PropertyMappingConfiguration
{

    /**
     * AllowAllPropertiesPropertyMappingConfiguration constructor.
     */
    public function __construct()
    {
        $this->allowAllProperties();
        $this->skipUnknownProperties();
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationFor($propertyName)
    {
        if (isset($this->subConfigurationForProperty[$propertyName])) {
            return $this->subConfigurationForProperty[$propertyName];
        } elseif (isset($this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER])) {
            return $this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER];
        }

        return new static();
    }

}
