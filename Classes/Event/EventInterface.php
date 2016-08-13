<?php
namespace Flowpack\Cqrs\Event;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Message\MessageInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * Event Interface
 */
interface EventInterface extends MessageInterface
{
    /**
     * Aggregate Root ID
     * @param string $identifier
     * @return EventInterface
     */
    public function setAggregateIdentifier(string $identifier): EventInterface;

    /**
     * Aggregate Root ID
     * @return string
     */
    public function getAggregateIdentifier();
}
