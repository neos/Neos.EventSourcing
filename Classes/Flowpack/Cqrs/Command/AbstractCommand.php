<?php
namespace Flowpack\Cqrs\Command;

/*
 * This file is part of the Medialib.Storage package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Domain\Timestamp;
use Flowpack\Cqrs\Exception;
use Flowpack\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;

/**
 * AbstractCommand
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * @var array
     */
    protected $payload = [];

    /**
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->setMetadata(get_called_class(), Timestamp::create());
    }

    /**
     * @param  string $name
     * @param  \DateTime $timestamp
     * @return void
     */
    public function setMetadata($name, \DateTime $timestamp)
    {
        $this->metadata = [
            'name' => $name,
            'timestamp' => $timestamp
        ];
    }

    /**
     * @return MessageMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param  array $payload
     * @return void
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Proxy to getMetadata()->getName()
     * @return string
     * @throws Exception
     */
    public function getName()
    {
        if (!isset($this->metadata['name'])) {
            throw new Exception('Empty name, invalid command');
        }
        return $this->metadata['name'];
    }

    /**
     * Proxy to getMetadata()->getTimestamp()
     * @return \DateTime
     * @throws Exception
     */
    public function getTimestamp()
    {
        if (!isset($this->metadata['timestamp'])) {
            throw new Exception('Empty timestamp, invalid command');
        }
        return $this->metadata['timestamp'];
    }
}
