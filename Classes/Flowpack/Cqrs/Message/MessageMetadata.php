<?php
namespace Flowpack\Cqrs\Message;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * MessageInterface
 */
class MessageMetadata
{
    /** @var string */
    protected $name;

    /** @var \DateTime */
    protected $timestamp;

    /**
     * MessageMetadata constructor
     * @param string $name
     * @param \DateTime $timestamp
     */
    public function __construct($name, \DateTime $timestamp)
    {
        $this->name = $name;
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
