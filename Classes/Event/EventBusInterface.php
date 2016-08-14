<?php
namespace Flowpack\Cqrs\Event;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Message\MessageBusInterface;
use Flowpack\Cqrs\Message\MessageInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventBusInterface
 */
interface EventBusInterface extends MessageBusInterface
{
}
