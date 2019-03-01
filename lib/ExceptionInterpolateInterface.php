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

interface ExceptionInterpolateInterface extends \Throwable
{
    /**
     * @param string|null $message
     * @param mixed       ...$parameters
     *
     * @return ExceptionInterface
     */
    public static function create(string $message = null, ...$parameters): ExceptionInterface;

    /**
     * @return string|null
     */
    public function getInputMessageFormat(): ?string;

    /**
     * @return array
     */
    public function getInputReplacements(): array;
}
