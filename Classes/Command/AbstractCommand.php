<?php
namespace Neos\Cqrs\Command;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Cqrs\Message\CausationAwareInterface;
use Neos\Cqrs\Message\CorrelationAwareInterface;
use Neos\Cqrs\Message\MessageIdentifierAwareInterface;

/**
 * Optional base class for commands.
 *
 * This class provides functionality for message, correlation and causation identifiers. As an alternative to
 * extending this class you can also create a standalone implementation which just implements the desired
 * interfaces.
 *
 * @api
 */
abstract class AbstractCommand implements CommandInterface, CorrelationAwareInterface, CausationAwareInterface, MessageIdentifierAwareInterface
{
    /**
     * @var string
     */
    protected $messageIdentifier;

    /**
     * @var string
     */
    protected $causationIdentifier;

    /**
     * @var string
     */
    protected $correlationIdentifier;

    /**
     * Return the message identifier (i.e. the command identifier)
     *
     * @return string
     */
    public function getMessageIdentifier()
    {
        return $this->messageIdentifier;
    }

    /**
     * Return the causation identifier
     *
     * @return string
     */
    public function getCausationIdentifier()
    {
        return $this->causationIdentifier;
    }

    /**
     * Allows to set the causation identifier once.
     *
     * On trying to set the causation identifier a second time this method throws a LogicException.
     *
     * @param string $causationIdentifier
     * @return void
     * @throws \LogicException
     */
    public function setCausationIdentifier(string $causationIdentifier)
    {
        if ($this->causationIdentifier !== null) {
            throw new \LogicException(sprintf('The causation identifier of %s has already been set and can\'t be set a second time.', get_class($this)), 1479323048881);
        }
        $this->causationIdentifier = $causationIdentifier;
    }

    /**
     * Return the correlation identifier
     *
     * @return string
     */
    public function getCorrelationIdentifier()
    {
        return $this->correlationIdentifier;
    }

    /**
     * Allows to set the correlation identifier once.
     *
     * On trying to set the correlation identifier a second time this method throws a LogicException.
     *
     * @param string $correlationIdentifier
     * @return void
     * @throws \LogicException
     */
    public function setCorrelationIdentifier(string $correlationIdentifier)
    {
        if ($this->correlationIdentifier !== null) {
            throw new \LogicException(sprintf('The correlation identifier of %s has already been set and can\'t be set a second time.', get_class($this)), 1479322981851);
        }
        $this->correlationIdentifier = $correlationIdentifier;
    }
}
