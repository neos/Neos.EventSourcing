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
     * @return MessageMetadata
     */
    public function getMetadata();
    
    /**
     * @return array
     */
    public function getPayload();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return \DateTime
     */
    public function getTimestamp();
}
