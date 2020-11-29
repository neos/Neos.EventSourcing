<?php

declare(strict_types=1);

namespace Neos\EventSourcing\Symfony\Transport;

use Neos\EventSourcing\Symfony\Resources\EventSourcingMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerTransport implements AsyncTransportInterface
{
    private $messageBus;

    public function __construct(
        MessageBusInterface $messageBus
    )
    {
        $this->messageBus = $messageBus;
    }

    public function send(
        string $listenerClassName,
        string $eventStoreContainerId
    )
    {
        $message = EventSourcingMessage::create(
            $listenerClassName,
            $eventStoreContainerId
        );
        $this->messageBus->dispatch(
            $message
        );
    }
}