<?php
namespace Ttree\Cqrs\Annotations;

/*
 * This file is part of the Medialib.Storage package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

/**
 * @Annotation
 * @Target("CLASS")
 */
final class EventHandler
{
    /**
     * @var string
     */
    public $subject;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['subject'])) {
            $this->subject = (string)$values['subject'];
        }
    }
}
