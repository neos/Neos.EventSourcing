<?php
namespace Flowpack\Cqrs\EventStore\EventSerializer;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Event\EventInterface;
use Flowpack\Cqrs\EventStore\Exception\EventSerializerException;
use Flowpack\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * EventSerializer
 */
class EventSerializer implements EventSerializerInterface
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @param EventInterface $event
     * @return array
     */
    public function serialize(EventInterface $event)
    {
        $payload = $event->getPayload();

        foreach ($payload as $key => &$value) {
            if ($value instanceof \DateTime) {
                $value = [
                    '_php_class' => \DateTime::class,
                    '_value' => $value->format(\DateTime::ISO8601)
                ];
            }
        }

        $data = [
            'class' => get_class($event),
            'identifier' => (string)$event->getAggregateIdentifier(),
            'name' => $event->getName(),
            'timestamp' => $event->getTimestamp()->format(\DateTime::ISO8601),
            'payload' => $payload
        ];
        return $data;
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
            'identifier',
            'name',
            'timestamp',
            'payload'
        ];

        // $data array structure validation
        if (count(array_intersect_key($schema, array_keys($data))) !== count($schema)) {
            throw new EventSerializerException('No event class specified or invalid entry');
        }

        $payload = $data['payload'];
        foreach ($payload as $key => &$value) {
            if (!is_array($value) || !array_key_exists('_php_class', $value)) {
                continue;
            }
            $value = $this->objectManager->get($value['_php_class'], $value['_value']);
        }

        /** @var EventInterface $event */
        $metaData = new MessageMetadata($data['name'], new \DateTime($data['timestamp']));
        $event = $this->objectManager->get($data['class'], $data['payload'], $metaData);

        $event->setAggregateIdentifier($data['identifier']);

        return $event;
    }
}
