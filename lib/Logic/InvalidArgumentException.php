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
 * Used when an unexpected argument type or value is encountered.
 */
class InvalidArgumentException extends \InvalidArgumentException implements LogicExceptionInterface
{
    use ExceptionTrait;
}
