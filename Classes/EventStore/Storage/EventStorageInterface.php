<?php
namespace Flowpack\Cqrs\EventStore\Storage;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\EventStore\EventStreamData;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventStorageInterface
 */
interface EventStorageInterface
{
    /**
     * @param string $identifier
     * @return EventStreamData Aggregate Root events
     */
    public function load(string $identifier);

    /**
     * @param string $aggregateIdentifier
     * @param string $aggregateName
     * @param array $data
     * @param integer $currentVersion
     * @param integer $nextVersion
     * @return void
     */
    public function commit(string $aggregateIdentifier, string $aggregateName, array $data, int $currentVersion, int $nextVersion);

    /**
     * @param string $identifier
     * @return boolean
     */
    public function contains(string $identifier): boolean;

    /**
     * @param  string $identifier
     * @return integer Current Aggregate Root version
     */
    public function getCurrentVersion(string $identifier): int;
}
