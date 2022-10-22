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
    public static function create(string $message = null, mixed ...$parameters): ExceptionInterface;

    public function getInputMessageFormat(): ?string;

    public function getInputReplacements(): array;
}
