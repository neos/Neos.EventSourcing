<?php

namespace Neos\EventSourcing\Symfony\Transport;

interface AsyncTransportInterface {

    public function send(
        string $listenerClassName,
        string $eventStoreContainerId
    );
}