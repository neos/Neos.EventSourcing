<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Fixture;

use Neos\EventSourcing\Event\DomainEventInterface;

final class EventWithValueObjects implements DomainEventInterface
{
    /**
     * @var ArrayValueObject
     */
    private $array;

    /**
     * @var StringValueObject
     */
    private $string;

    /**
     * @var IntegerValueObject
     */
    private $integer;

    /**
     * @var FloatValueObject
     */
    private $float;

    /**
     * @var BooleanValueObject
     */
    private $boolean;

    public function __construct(ArrayValueObject $array, StringValueObject $string, IntegerValueObject $integer, FloatValueObject $float, BooleanValueObject $boolean)
    {
        $this->array = $array;
        $this->string = $string;
        $this->integer = $integer;
        $this->float = $float;
        $this->boolean = $boolean;
    }

    /**
     * @return ArrayValueObject
     */
    public function getArray(): ArrayValueObject
    {
        return $this->array;
    }

    /**
     * @return StringValueObject
     */
    public function getString(): StringValueObject
    {
        return $this->string;
    }

    /**
     * @return IntegerValueObject
     */
    public function getInteger(): IntegerValueObject
    {
        return $this->integer;
    }

    /**
     * @return FloatValueObject
     */
    public function getFloat(): FloatValueObject
    {
        return $this->float;
    }

    /**
     * @return BooleanValueObject
     */
    public function getBoolean(): BooleanValueObject
    {
        return $this->boolean;
    }

    public function equals(self $other): bool
    {
        return $other->array->equals($this->array)
            && $other->string->equals($this->string)
            && $other->integer->equals($this->integer)
            && $other->float->equals($this->float)
            && $other->boolean->equals($this->boolean);
    }

}
