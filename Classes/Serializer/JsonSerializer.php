<?php
namespace Neos\Cqrs\Serializer;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class JsonSerializer
{
    /**
     * @var ArraySerializer
     * @Flow\Inject
     */
    protected $arraySerializer;

    /**
     * @param object $object
     * @return string
     */
    public function serialize($object)
    {
        return json_encode(
            $this->arraySerializer->serialize($object), JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION
        );
    }

    /**
     * @param string $serializedMessage
     * @return mixed
     */
    public function unserialize($serializedMessage)
    {
        if (is_string($serializedMessage) === false) {
            throw new \InvalidArgumentException('The JsonSerializer can only unserialize strings.', 1427369767);
        }
        return $this->arraySerializer->unserialize(
            json_decode($serializedMessage, true)
        );
    }
}
