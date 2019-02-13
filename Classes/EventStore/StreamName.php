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

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('The stream name must not be empty', 1540311275);
        }
        if (strpos($value, '$') === 0) {
            throw new \InvalidArgumentException('The stream name must not start with "$"', 1540632865);
        }
        return new static($value);
    }

    public static function forCategory(string $categoryName): self
    {
        $categoryName = trim($categoryName);
        if ($categoryName === '') {
            throw new \InvalidArgumentException('The category name must not be empty', 1540632813);
        }
        if (strpos($categoryName, '$') === 0) {
            throw new \InvalidArgumentException('The category name must not start with "$"', 1540632884);
        }
        return new static('$ce-' . $categoryName);
    }

    public static function forCorrelationId(string $correlationId): self
    {
        $correlationId = trim($correlationId);
        if ($correlationId === '') {
            throw new \InvalidArgumentException('The correlation identifier must not be empty', 1540899054);
        }
        if (strpos($correlationId, '$') === 0) {
            throw new \InvalidArgumentException('The correlation identifier must not start with "$"', 1540899066);
        }
        return new static('$correlation-' . $correlationId);
    }

    public static function all(): self
    {
        return new static('$all');
    }

    public function isVirtualStream(): bool
    {
        return strpos($this->value, '$') === 0;
    }

    public function isAllStream(): bool
    {
        return $this->value === '$all';
    }

    public function isCategoryStream(): bool
    {
        return strpos($this->value, '$ce-') === 0;
    }

    public function isCorrelationIdStream(): bool
    {
        return strpos($this->value, '$correlation-') === 0;
    }

    public function getCategoryName(): string
    {
        if (!$this->isCategoryStream()) {
            throw new \RuntimeException(sprintf('Stream "%s" is no category stream', $this->value), 1540633414);
        }
        return substr($this->value, 4);
    }

    public function equals(StreamName $otherStreamName): bool
    {
        return $this->value === $otherStreamName->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
