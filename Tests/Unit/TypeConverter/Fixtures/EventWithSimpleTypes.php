<?php
namespace Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures;

use Neos\EventSourcing\Event\EventInterface;

final class EventWithSimpleTypes implements EventInterface
{
    /**
     * @var string
     */
    private $someString;

    /**
     * @var bool
     */
    private $someBoolean;

    /**
     * @var int
     */
    private $someInteger;

    /**
     * @var float
     */
    private $someFloat;

    public function __construct(string $someString, bool $someBoolean, int $someInteger, float $someFloat)
    {
        $this->someString = $someString;
        $this->someBoolean = $someBoolean;
        $this->someInteger = $someInteger;
        $this->someFloat = $someFloat;
    }

    public function getSomeString(): string
    {
        return $this->someString;
    }

    public function isSomeBoolean(): bool
    {
        return $this->someBoolean;
    }

    public function getSomeInteger(): int
    {
        return $this->someInteger;
    }

    public function getSomeFloat(): float
    {
        return $this->someFloat;
    }

}