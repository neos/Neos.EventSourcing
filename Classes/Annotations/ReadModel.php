<?php
namespace Neos\Cqrs\Annotations;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * @Annotation
 * @Target("CLASS")
 */
final class ReadModel
{
    /**
     * @var string
     */
    public $table;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['table'])) {
            $this->table = $values['table'];
        }
    }
}
