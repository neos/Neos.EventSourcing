<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Fixture;

final class BooleanValueObject implements \JsonSerializable
{
    private $value;

    private function __construct(bool $value)
    {
        $this->value = $value;
    }

    public static function fromBoolean(bool $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }

    public function jsonSerialize(): bool
    {
        return $this->value;
    }
}
