<?php
namespace Flowpack\Cqrs\EventStore\EventSerializer;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Domain\Uuid;
use Flowpack\Cqrs\Event\EventInterface;
use Flowpack\Cqrs\EventStore\Exception\EventSerializerException;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventSerializer
 */
class EventSerializer implements EventSerializerInterface
{
    /**
     * @param EventInterface $event
     * @return array
     */
    public function serialize(EventInterface $event)
    {
        $payload = $event->getPayload();

        foreach ($payload as $key => &$value) {

            if ($value instanceof Uuid) {
                $value = [
                    '_php_class' => Uuid::class,
                    '_value' => (string)$value
                ];
            }

            if ($value instanceof \DateTime) {
                $value = [
                    '_php_class' => \DateTime::class,
                    '_value' => $value->format(\DateTime::ISO8601)
                ];
            }
        }

        return [
            'class' => get_class($event),
            'id' => (string)$event->getId(),
            'name' => $event->getName(),
            'timestamp' => $event->getTimestamp()->format(\DateTime::ISO8601),
            'payload' => $payload
        ];
    }

    /**
     * @param array $data
     * @return EventInterface
     * @throws EventSerializerException
     */
    public function deserialize(array $data)
    {
        $schema = [
            'class',
            'id',
            'name',
            'timestamp',
            'payload'
        ];

        // $data array structure validation
        if (count(array_intersect_key($schema, $data)) !== count($schema)) {
            throw new EventSerializerException('No event class specified or invalid entry');
        }

        $reflection = new \ReflectionClass($data['class']);

        $payload = $data['payload'];

        foreach ($payload as $key => &$value) {
            if (!is_array($value) || !array_key_exists('_php_class', $value)) {
                continue;
            }

            $class = new \ReflectionClass($value['_php_class']);
            $value = $class->newInstanceArgs($value['_value']);
        }

        /** @var EventInterface $event */
        $event = $reflection->newInstanceArgs($data['payload']);
        $event->setMetadata(
            $data['name'],
            new \DateTime($data['timestamp'])
        );
        $event->setId(new Uuid($data['id']));

        return $event;
    }
}
