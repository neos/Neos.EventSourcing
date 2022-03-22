<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Fixture;

final class IntegerValueObject implements \JsonSerializable
{
    private $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function fromInteger(int $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
