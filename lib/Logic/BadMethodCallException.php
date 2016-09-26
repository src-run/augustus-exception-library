<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception\Logic;

use SR\Exception\ExceptionTrait;

/**
 * Intended to be used if an undefined or otherwise un-callable method reference is encountered.
 */
class BadMethodCallException extends \BadMethodCallException implements LogicExceptionInterface
{
    use ExceptionTrait;

    /**
     * Constructor accepts message string and any number of parameters, which will be used as string replacements for
     * message string (unless an instance of \Throwable is found, in which case it is passed to parent as previous).
     *
     * @param null|string $message
     * @param mixed       ...$params
     */
    public function __construct(string $message = null, ...$params)
    {
        parent::__construct($this->compileMessage($message ?: $this->defaultMessage(), $params), null, $this->compileThrown($params));
    }
}

/* EOF */
