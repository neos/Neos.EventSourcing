<?php
namespace Flowpack\Cqrs\Event;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Domain\Uuid;
use Flowpack\Cqrs\Message\MessageInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventInterface
 */
interface EventInterface extends MessageInterface
{
    /**
     * Aggregate Root ID
     * @param  Uuid $id
     * @return void
     */
    public function setId(Uuid $id);

    /**
     * Aggregate Root ID
     * @return Uuid
     */
    public function getId();
}
