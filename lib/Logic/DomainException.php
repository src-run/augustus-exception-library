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
 * Used when a value does not adhere to a defined data domain.
 */
class DomainException extends \DomainException implements LogicExceptionInterface
{
    use ExceptionTrait;
}
