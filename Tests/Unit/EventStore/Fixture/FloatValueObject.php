<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Fixture;

final class FloatValueObject implements \JsonSerializable
{
    private $value;

    private function __construct(float $value)
    {
        $this->value = $value;
    }

    public static function fromFloat(float $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }

    public function jsonSerialize(): float
    {
        return $this->value;
    }
}
