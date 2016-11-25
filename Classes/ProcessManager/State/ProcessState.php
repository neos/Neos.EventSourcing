<?php
namespace Neos\Cqrs\ProcessManager\State;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * Process State
 *
 * All methods which change the data of this state will trigger automatic persistence. This is necessary in order to
 * support rows of commands and events handled by a single process manager which are executed synchronously within
 * a single PHP request.
 *
 * @Flow\Entity
 */
class ProcessState
{
    /**
     * @ORM\Id
     * @ORM\Column(length=40)
     * @var string
     */
    protected $identifier;

    /**
     * @ORM\Id
     * @var string
     */
    protected $processManagerClassName;

    /**
     * @Flow\Transient
     * @var bool
     */
    protected $done = false;

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var array
     */
    protected $checklist = [];

    /**
     * @Flow\Inject
     * @var StateRepository
     */
    protected $stateRepository;

    /**
     * State constructor.
     *
     * @param string $identifier
     * @param string $processManagerClassName
     */
    public function __construct($identifier, $processManagerClassName)
    {
        $this->identifier = $identifier;
        $this->processManagerClassName = $processManagerClassName;
    }

    /**
     * Persist this state right after it has been instantiated.
     *
     * @param int $initializationCause
     */
    public function initializeObject(int $initializationCause)
    {
        if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
            $this->stateRepository->save($this);
        }
    }

    /**
     * Returns the identifier of this State
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns the class name of the process manager this State is serving
     *
     * @return string
     */
    public function getProcessManagerClassName()
    {
        return $this->processManagerClassName;
    }

    /**
     * Marks this process as done
     */
    public function setDone()
    {
        $this->done = true;
        $this->stateRepository->remove($this);
    }

    /**
     * Tells if this process is done
     *
     * @return boolean
     */
    public function isDone()
    {
        return $this->done;
    }

    /**
     * Set a state property
     *
     * @param string $propertyName
     * @param mixed $propertyValue
     */
    public function set(string $propertyName, $propertyValue)
    {
        $this->properties[$propertyName] = $propertyValue;
        $this->stateRepository->save($this);
    }

    /**
     * Retrieve a state property
     *
     * @param string $propertyName
     * @return mixed|null
     */
    public function get(string $propertyName)
    {
        return isset($this->properties[$propertyName]) ? $this->properties[$propertyName] : null;
    }

    /**
     * Returns all properties of this state
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Sets a new checklist
     *
     * @param array $items Todos to check tick off
     */
    public function setChecklist(array $items)
    {
        $checklist = [];
        foreach ($items as $item) {
            if (!is_string($item)) {
                throw new \InvalidArgumentException(sprintf('Invalid check list item for process state. Items must be simple strings, %s given.', gettype($item)), 1479243504059);
            }
            $checklist[$item] = false;
        }
        $this->checklist = $checklist;
        $this->stateRepository->save($this);
    }

    /**
     * Marks an item of the checklist as done.
     *
     * If all items are done, the whole process is marked as done automatically.
     *
     * @param string $name Name of the checklist item
     */
    public function setChecklistItemDone(string $name)
    {
        if (!isset($this->checklist[$name])) {
            return;
        }

        $this->checklist[$name] = true;
        $this->stateRepository->save($this);

        if ($this->isChecklistDone()) {
            $this->setDone();
        }
    }

    /**
     * Tells if the current checklist (if any) is done
     *
     * @return bool
     */
    public function isChecklistDone()
    {
        if ($this->checklist === []) {
            return false;
        }
        foreach ($this->checklist as $name => $value) {
            if ($value !== true) {
                return false;
            }
        }
        return true;
    }
}
