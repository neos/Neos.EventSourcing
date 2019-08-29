<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Normalizer\Fixture;

class ArrayBasedValueObject
{
    public static function fromArray(array $value): self
    {
        return new self();
    }
}
