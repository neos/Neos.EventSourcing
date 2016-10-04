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
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\TypeHandling;

/**
 * @Flow\Scope("singleton")
 */
class ArraySerializer
{
    /**
     * @param object $object
     * @return array
     */
    public function serialize($object)
    {
        $type = str_replace('\\', '.', TypeHandling::getTypeForValue($object));
        $data = ObjectAccess::getGettableProperties($object);
        return Arrays::arrayMergeRecursiveOverrule([
            '__type' => $type,
        ], $data);
    }

    /**
     * @param array $serializedObject
     * @return mixed
     */
    public function unserialize($serializedObject)
    {
        if (is_array($serializedObject) === false) {
            throw new \InvalidArgumentException('The ArraySerializer can only unserialize arrays.', 1427369045);
        }
        if (array_key_exists('__type', $serializedObject) === false) {
            throw new \InvalidArgumentException('The serialized object is corrupted.', 1427369459);
        }
        $type = str_replace('.', '\\', $serializedObject['__type']);
        if (class_exists($type) === false) {
            throw new \InvalidArgumentException('Unserialization for object of type "' . $type . '" failed. No such class.', 1427369534);
        }
        $object = unserialize('O:' . strlen($type) . ':"' . $type . '":0:{};');
        $payload = $serializedObject;
        unset($payload['__type']);
        foreach ($payload as $propertyName => $propertyValue) {
            if (is_array($propertyValue) &&
                count($propertyValue) === 3 &&
                isset($propertyValue['date']) && isset($propertyValue['timezone']) && isset($propertyValue['timezone_type'])
            ) {
                $propertyValue = new \DateTimeImmutable($propertyValue['date']);
            }
            ObjectAccess::setProperty($object, $propertyName, $propertyValue, true);
        }
        return $object;
    }
}
