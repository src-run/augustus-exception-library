<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception;

/**
 * Class BadFunctionCallException.
 */
class BadFunctionCallException extends \BadFunctionCallException implements ExceptionInterface
{
    use ExceptionTrait;

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     */
    final public function __construct($message = null, ...$parameters)
    {
        parent::__construct($this->compileMessage($message, $parameters), 0, $this->filterThrowables($parameters));
    }
}

/* EOF */
