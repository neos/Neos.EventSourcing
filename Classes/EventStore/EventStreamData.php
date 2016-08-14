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
    protected $aggregateIdentifier;

    /** @var string Aggregate Root name */
    protected $name;

    /** @var int */
    protected $version;

    /** @var array */
    protected $data;

    /**
     * @param string $aggregateIdentifier
     * @param string $name
     * @param array $data
     * @param integer $version
     */
    public function __construct(string $aggregateIdentifier, string $name, array $data, int $version)
    {
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->name = $name;
        $this->version = $version;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getAggregateIdentifier(): string
    {
        return $this->aggregateIdentifier;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
