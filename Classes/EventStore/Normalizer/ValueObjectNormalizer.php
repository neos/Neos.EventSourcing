<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventStore\Normalizer;

use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Utility\TypeHandling;
use ReflectionMethod;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * A normalizer that can convert scalar values (string, integer, double or boolean) to a class
 * that expects exactly one constructor argument of the corresponding type (including type hint)
 *
 * Named constructors in the form "from<Type>" are considered too if:
 * * they are public
 * * they are static
 * * they expect a single parameter of the given type
 * * they have a "self" or "<TargetClassName>" return type annotation
 *
 * Note: For type "array" a named constructor fromArray() is required!
 */
final class ValueObjectNormalizer implements DenormalizerInterface
{
    /**
     * @var array
     */
    private $resolveConstructorMethodCache = [];

    /**
     * @var array
     */
    private $resolveNamedConstructorMethodCache = [];

    public function denormalize($data, $className, $format = null, array $context = [])
    {
        $constructorMethod = $this->resolveConstructorMethod(TypeHandling::normalizeType(TypeHandling::getTypeForValue($data)), $className);
        return $constructorMethod->isConstructor() ? new $className($data) : $constructorMethod->invoke(null, $data);
    }

    public function supportsDenormalization($data, $className, $format = null): bool
    {
        $supportedTypes = ['array', 'string', 'integer', 'float', 'boolean'];
        $dataType = TypeHandling::normalizeType(TypeHandling::getTypeForValue($data));
        if (!\in_array($dataType, $supportedTypes, true)) {
            return false;
        }
        try {
            $this->resolveConstructorMethod($dataType, $className);
            return true;
        } catch (\InvalidArgumentException $exception) {
            return false;
        }
    }

    private function resolveConstructorMethod(string $dataType, string $className): ReflectionMethod
    {
        $cacheIdentifier = md5($dataType . '|' . $className);
        if (array_key_exists($cacheIdentifier, $this->resolveConstructorMethodCache)) {
            return $this->resolveConstructorMethodCache[$cacheIdentifier];
        }

        try {
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException $exception) {
            throw new \InvalidArgumentException(sprintf('Could not reflect class "%s"', $className), 1545233370, $exception);
        }
        if ($reflectionClass->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('Class "%s" is abstract', $className), 1545296135);
        }
        $namedConstructorMethod = $this->resolveNamedConstructorMethod($dataType, $className, $reflectionClass);
        if ($dataType === 'array' && $namedConstructorMethod === null) {
            throw new \InvalidArgumentException(sprintf('Missing named constructor static public function fromArray(array $foo): self in class "%s"', $className), 1569500780);
        }
        if ($namedConstructorMethod !== null) {
            $constructorMethod = $namedConstructorMethod;
        } else {
            $constructorMethod = $reflectionClass->implementsInterface(ProxyInterface::class) ? $reflectionClass->getParentClass()->getConstructor() : $reflectionClass->getConstructor();
        }
        if ($constructorMethod === null) {
            throw new \InvalidArgumentException(sprintf('Could not resolve constructor for class "%s"', $className), 1545233397);
        }
        if (!$constructorMethod->isPublic()) {
            throw new \InvalidArgumentException(sprintf('Constructor %s:%s is not public', $className, $constructorMethod->getName()), 1545233434);
        }
        if ($constructorMethod->getNumberOfParameters() !== 1) {
            throw new \InvalidArgumentException(sprintf('Constructor %s:%s has %d parameter but it must have one', $className, $constructorMethod->getName(), $constructorMethod->getNumberOfParameters()), 1545233460);
        }
        $constructorParameter = $constructorMethod->getParameters()[0];
        $constructorParameterType = $constructorParameter->getType();
        if ($constructorParameterType === null || TypeHandling::normalizeType($constructorParameterType->getName()) !== $dataType) {
            throw new \InvalidArgumentException(sprintf('The constructor %s:%s expects a different parameter type', $className, $constructorMethod->getName()), 1545233522);
        }

        $this->resolveConstructorMethodCache[$cacheIdentifier] = $constructorMethod;
        return $constructorMethod;
    }

    private function resolveNamedConstructorMethod(string $dataType, string $className, \ReflectionClass $reflectionClass): ?ReflectionMethod
    {
        $cacheIdentifier = md5($dataType . '|' . $className);
        if (array_key_exists($cacheIdentifier, $this->resolveNamedConstructorMethodCache)) {
            return $this->resolveNamedConstructorMethodCache[$cacheIdentifier];
        }
        $staticConstructorName = 'from' . ucfirst($dataType);
        try {
            $this->resolveNamedConstructorMethodCache[$cacheIdentifier] = null;
            $constructorMethod = $reflectionClass->getMethod($staticConstructorName);
        } catch (\ReflectionException $exception) {
            return null;
        }
        if (!$constructorMethod->isStatic()) {
            return null;
        }
        $constructorMethodReturnType = $constructorMethod->getReturnType();
        if ($constructorMethodReturnType === null || $constructorMethodReturnType->allowsNull()) {
            return null;
        }
        $constructorMethodReturnTypeName = $constructorMethodReturnType->getName();
        if ($constructorMethodReturnTypeName !== $className && $constructorMethodReturnTypeName !== 'self') {
            return null;
        }
        $this->resolveNamedConstructorMethodCache[$cacheIdentifier] = $constructorMethod;
        return $constructorMethod;
    }
}
