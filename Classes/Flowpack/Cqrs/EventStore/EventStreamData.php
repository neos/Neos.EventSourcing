<?php
namespace Flowpack\Cqrs\EventStore;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * EventStreamData
 */
class EventStreamData
{
    /** @var string Aggregate Root ID */
    public $id;

    /** @var string Aggregate Root name */
    public $name;

    /** @var int */
    public $version;

    /** @var array */
    public $data;
}
