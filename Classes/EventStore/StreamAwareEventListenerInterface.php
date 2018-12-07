<?php
namespace Neos\EventSourcing\EventStore;

interface StreamAwareEventListenerInterface
{
    public static function listensToStream(): StreamName;
}
