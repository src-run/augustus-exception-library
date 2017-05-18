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
 * Used when performing an invalid operation on an empty container (ex: removing an invalid element).
 */
class UnderflowException extends \UnderflowException implements RuntimeExceptionInterface
{
    use ExceptionTrait;
}
