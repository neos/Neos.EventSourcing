<?php

declare(strict_types=1);

namespace Neos\EventSourcing\Symfony\Resources;

final class EventSourcingMessage
{
    public $listenerClassName;

    public $eventStoreContainerId;

    public static function create(
        string $listenerClassName,
        string $eventStoreContainerId
    ): EventSourcingMessage
    {

        $newMessage = new self();
        $newMessage->listenerClassName = $listenerClassName;
        $newMessage->eventStoreContainerId = $eventStoreContainerId;

        return $newMessage;
    }
}
