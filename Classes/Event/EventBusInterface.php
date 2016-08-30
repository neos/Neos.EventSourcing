<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

/**
 * EventBusInterface
 */
interface EventBusInterface
{
    /**
     * @param EventTransport $transport
     * @return void
     */
    public function handle(EventTransport $transport);
}
