<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Fixture;

final class ArrayValueObject implements \JsonSerializable
{
    private $value;

    private function __construct(array $value)
    {
        $this->value = $value;
    }

    public static function fromArray(array $value): ArrayValueObject
    {
        return new ArrayValueObject($value);
    }

    public function equals(ArrayValueObject $other): bool
    {
        return $other->value === $this->value;
    }

    public function jsonSerialize(): array
    {
        return $this->value;
    }
}
