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
    protected $id;

    /** @var string Aggregate Root name */
    protected $name;

    /** @var int */
    protected $version;

    /** @var array */
    protected $data;

    /**
     * EventStreamData constructor.
     * @param string $id
     * @param string $name
     * @param array $data
     * @param integer $version
     */
    public function __construct(string $id, string $name, array $data, int $version)
    {
        $this->id = (string)$id;
        $this->name = (string)$name;
        $this->version = (integer)$version;
        $this->data = (array)$data;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
