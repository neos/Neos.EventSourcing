<?php

declare(strict_types=1);

namespace Neos\EventSourcing\Symfony\Transport;

use Neos\EventSourcing\Symfony\Command\InternalCatchUpEventListenerCommand;
use Symfony\Component\Process\Process;

class ConsoleCommandTransport implements AsyncTransportInterface
{
    public function send(
        string $listenerClassName,
        string $eventStoreContainerId
    )
    {
        $process = new Process(
            [
                'php',
                '/var/www/eventsourcing.app/bin/console',
                InternalCatchUpEventListenerCommand::getDefaultName(),
                $listenerClassName,
                $eventStoreContainerId
            ]
        );
        $process->run(); // !! WE DO NOT WAIT FOR THE RESULT - start !! - TODO: adjust
        $errOut = $process->getOutput() . $process->getErrorOutput();
    }
}