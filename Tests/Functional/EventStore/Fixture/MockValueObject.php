<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Functional\EventStore\Fixture;

final class MockValueObject implements \JsonSerializable
{
    /**
     * @var string
     */
    private $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function getString(): string
    {
        return $this->string;
    }

    public function jsonSerialize(): string
    {
        return $this->string;
    }
}
