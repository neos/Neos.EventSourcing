<?php
namespace Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures;

use Neos\EventSourcing\Event\EventInterface;

require_once(__DIR__ . '/JsonSerializableEvent.php');

final class EventWithJsonSerializableProperties implements EventInterface
{
    /**
     * @var JsonSerializableEvent
     */
    private $serializableProperty;

    /**
     * @param JsonSerializableEvent $serializableProperty
     */
    public function __construct(JsonSerializableEvent $serializableProperty)
    {
        $this->serializableProperty = $serializableProperty;
    }

    public function getSerializableProperty(): JsonSerializableEvent
    {
        return $this->serializableProperty;
    }

}