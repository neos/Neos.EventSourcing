<?php
namespace Flowpack\Cqrs\EventStore\Storage;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\EventStore\EventStreamData;
use Flowpack\Cqrs\EventStore\Exception\ConcurrencyException;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventStore
 */
class InMemoryEventStorage implements EventStorageInterface
{
    /**
     * @var array
     */
    protected $streamData = [];

    /**
     * @param string $identifier
     * @return EventStreamData
     */
    public function load(string $identifier)
    {
        if (isset($this->streamData[$identifier])) {
            return $this->streamData[$identifier];
        }
        return null;
    }

    /**
     * @param string $identifier
     * @param string $aggregateName
     * @param array $data
     * @param integer $currentVersion
     * @param integer $nextVersion
     * @throws ConcurrencyException
     */
    public function commit(string $identifier, string $aggregateName, array $data, int $currentVersion, int $nextVersion)
    {
        if (isset($this->streamData[$identifier]) && $this->streamData[$identifier]->getVersion() !== $currentVersion) {
            throw new ConcurrencyException(
                sprintf('Version %d does not match current version %d', $this->streamData[$identifier]->getVersion(), $currentVersion)
            );
        }
        if (isset($this->streamData[$identifier])) {
            $currentData = $this->streamData[$identifier]->getData();
            $data = array_merge($currentData, $data);
        }
        $this->streamData[$identifier] = new EventStreamData($identifier, $aggregateName, $data, $nextVersion);
    }

    /**
     * @param string $identifier
     * @return boolean
     */
    public function contains(string $identifier): boolean
    {
        return isset($this->streamData[$identifier]);
    }

    /**
     * @param  string $identifier
     * @return integer Current Aggregate Root version
     */
    public function getCurrentVersion(string $identifier): int
    {
        if (isset($this->streamData[$identifier])) {
            return $this->streamData[$identifier]->getVersion();
        }
        return 1;
    }
}
