<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 * (c) Scribe Inc      <scr@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception;

/**
 * Class RuntimeException.
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
    use ExceptionTrait;

    /**
     * @return string
     */
    public function getDefaultMessage()
    {
        return ExceptionInterface::MSG_RUNTIME;
    }

    /**
     * @return int
     */
    public function getDefaultCode()
    {
        return ExceptionInterface::CODE_RUNTIME;
    }
}

/* EOF */
