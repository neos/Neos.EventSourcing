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
     * @var StreamName[]
     */
    private static $instances = [];

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    private static function constant(string $value): StreamName
    {
        return StreamName::$instances[$value] ?? StreamName::$instances[$value] = new StreamName($value);
    }

    public static function fromString(string $value): StreamName
    {
        $value = StreamName::trimAndValidateNotEmpty($value);
        if (StreamName::stringStartsWith($value, '$')) {
            throw new \InvalidArgumentException('The stream name must not start with "$"', 1540632865);
        }
        return StreamName::constant($value);
    }

    public static function forCategory(string $categoryName): StreamName
    {
        $categoryName = StreamName::trimAndValidateNotEmpty($categoryName);
        if (StreamName::stringStartsWith($categoryName, '$')) {
            throw new \InvalidArgumentException('The category name must not start with "$"', 1540632884);
        }
        return StreamName::constant('$ce-' . $categoryName);
    }

    public static function forCorrelationId(string $correlationId): StreamName
    {
        $correlationId = StreamName::trimAndValidateNotEmpty($correlationId);
        if (StreamName::stringStartsWith($correlationId, '$')) {
            throw new \InvalidArgumentException('The correlation identifier must not start with "$"', 1540899066);
        }
        return StreamName::constant('$correlation-' . $correlationId);
    }

    public static function all(): StreamName
    {
        return StreamName::constant('$all');
    }

    public function isVirtualStream(): bool
    {
        return StreamName::stringStartsWith($this->value, '$');
    }

    public function isAllStream(): bool
    {
        return $this->value === '$all';
    }

    public function isCategoryStream(): bool
    {
        return StreamName::stringStartsWith($this->value, '$ce-');
    }

    public function isCorrelationIdStream(): bool
    {
        return StreamName::stringStartsWith($this->value, '$correlation-');
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
