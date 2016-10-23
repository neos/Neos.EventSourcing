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

final class WritableEvent implements WritableToStreamInterface
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
     * @return array
     */
    public function toStreamData()
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'metadata' => $this->metadata
        ];
    }
}
