<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventStore;

interface StreamAwareEventListenerInterface
{
    public static function listensToStream(): StreamName;
}
