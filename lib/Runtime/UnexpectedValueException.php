<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception\Runtime;

use SR\Exception\ExceptionTrait;

/**
 * Used when a given value does not match the expected set of values.
 */
class UnexpectedValueException extends \UnexpectedValueException implements RuntimeExceptionInterface
{
    use ExceptionTrait;
}
