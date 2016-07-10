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
interface MessageInterface
{
    /**
     * @param  string $name
     * @param  \DateTime $timestamp
     * @return void
     */
    public function setMetadata($name, \DateTime $timestamp);

    /**
     * @return MessageMetadata
     */
    public function getMetadata();

    /**
     * @param  array $payload
     * @return void
     */
    public function setPayload(array $payload);
    
    /**
     * @return array
     */
    public function getPayload();

    /**
     * Proxy to getMetadata()->getName()
     * @return string
     */
    public function getName();

    /**
     * Proxy to getMetadata()->getTimestamp()
     * @return \DateTime
     */
    public function getTimestamp();
}
