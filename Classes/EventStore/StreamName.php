<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventStore;

use Neos\Flow\Annotations as Flow;

/**
 * The stream name can be any (non-empty) string, but usually it has the format "<Bounded.Context>:<Aggregate>-<Identifier>"
 *
 * @Flow\Proxy(false)
 */
final class StreamName
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var self[]
     */
    private static $instances = [];

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    private static function constant(string $value): self
    {
        return self::$instances[$value] ?? self::$instances[$value] = new self($value);
    }

    public static function fromString(string $value): self
    {
        $value = self::trimAndValidateNotEmpty($value);
        if (self::stringStartsWith($value, '$')) {
            throw new \InvalidArgumentException('The stream name must not start with "$"', 1540632865);
        }
        return self::constant($value);
    }

    public static function forCategory(string $categoryName): self
    {
        $categoryName = self::trimAndValidateNotEmpty($categoryName);
        if (self::stringStartsWith($categoryName, '$')) {
            throw new \InvalidArgumentException('The category name must not start with "$"', 1540632884);
        }
        return self::constant('$ce-' . $categoryName);
    }

    public static function forCorrelationId(string $correlationId): self
    {
        $correlationId = self::trimAndValidateNotEmpty($correlationId);
        if (self::stringStartsWith($correlationId, '$')) {
            throw new \InvalidArgumentException('The correlation identifier must not start with "$"', 1540899066);
        }
        return self::constant('$correlation-' . $correlationId);
    }

    public static function all(): self
    {
        return self::constant('$all');
    }

    public function isVirtualStream(): bool
    {
        return self::stringStartsWith($this->value, '$');
    }

    public function isAllStream(): bool
    {
        return $this->value === '$all';
    }

    public function isCategoryStream(): bool
    {
        return self::stringStartsWith($this->value, '$ce-');
    }

    public function isCorrelationIdStream(): bool
    {
        return self::stringStartsWith($this->value, '$correlation-');
    }

    public function getCategoryName(): string
    {
        if (!$this->isCategoryStream()) {
            throw new \RuntimeException(sprintf('Stream "%s" is no category stream', $this->value), 1540633414);
        }
        return substr($this->value, 4);
    }

    public function getCorrelationId(): string
    {
        if (!$this->isCorrelationIdStream()) {
            throw new \RuntimeException(sprintf('Stream "%s" is no correlation id stream', $this->value), 1569398802);
        }
        return substr($this->value, 13);
    }

    private static function trimAndValidateNotEmpty(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('The value must not be empty', 1540311275);
        }
        return $value;
    }

    private static function stringStartsWith(string $string, string $search): bool
    {
        return strncmp($string, $search, \strlen($search)) === 0;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
