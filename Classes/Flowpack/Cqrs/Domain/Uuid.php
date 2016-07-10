<?php
namespace Flowpack\Cqrs\Domain;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Algorithms;

/**
 * Uuid
 */
class Uuid
{
    /** @var string */
    protected $uuid;

    /**
     * @param string|null $uuid
     */
    public function __construct($uuid = null)
    {
        if ($uuid instanceof Uuid) {
            $uuid = $uuid->toString();
        } elseif (null === $uuid) {
            $uuid = Algorithms::generateUUID();
        } elseif ($this->validate($uuid) === false) {
            throw new \InvalidArgumentException('Invalid UUID string given');
        }

        $this->uuid = trim($uuid);
    }

    /**
     * @return string|null
     */
    public function toString()
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return integer
     */
    public function validate($uuid)
    {
        return preg_match("/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i", $uuid);
    }

    /**
     * @return string|null
     */
    public function __toString()
    {
        return $this->toString();
    }
}
