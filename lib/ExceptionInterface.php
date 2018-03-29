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

interface ExceptionInterface extends ExceptionAttributesInterface, ExceptionContextInterface, ExceptionInterpolateInterface
{
    /**
     * Return string representation of exception.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Return array representation of exception.
     *
     * @return mixed[]
     */
    public function __toArray(): array;

    /**
     * Returns the exception type (class name) as either a fully-qualified class name or as just the class base name.
     *
     * @param bool $qualified
     *
     * @return string
     */
    public function getType(bool $qualified = false): string;
}
