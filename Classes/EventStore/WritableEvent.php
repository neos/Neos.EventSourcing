<?php
namespace Neos\Cqrs\EventStore;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class WritableEvent
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $metadata;

    public function __construct(string $type, array $data, array $metadata)
    {
        $this->type = $type;
        $this->data = $data;
        $this->metadata = $metadata;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
