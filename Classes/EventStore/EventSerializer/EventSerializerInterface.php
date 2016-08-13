<?php
namespace Flowpack\Cqrs\EventStore\EventSerializer;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Event\EventInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventSerializerInterface
 */
interface EventSerializerInterface
{
    /**
     * @param  EventInterface $event
     * @return array
     */
    public function serialize(EventInterface $event);

    /**
     * @param  array $data
     * @return EventInterface
     */
    public function deserialize(array $data);
}
