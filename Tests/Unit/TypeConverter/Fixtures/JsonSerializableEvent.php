<?php
namespace Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures;

use Neos\EventSourcing\Event\EventInterface;

final class JsonSerializableEvent implements EventInterface, \JsonSerializable
{
    public function jsonSerialize()
    {
        return ['custom' => 'serialization'];
    }
}