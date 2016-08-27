<?php
namespace Ttree\Cqrs\Command;

/*
 * This file is part of the Medialib.Storage package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

/**
 * CommandTrait
 */
trait CommandTrait
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        if ($this->identifier === null) {
            $this->identifier = \TYPO3\Flow\Utility\Algorithms::generateUUID();
        }
        return $this->identifier;
    }
}
