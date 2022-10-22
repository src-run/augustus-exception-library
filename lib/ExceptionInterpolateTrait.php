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

use SR\Exception\Utility\Dumper\Transformer\StringTransformer;
use SR\Exception\Utility\Interpolator\StringInterpolator;

trait ExceptionInterpolateTrait
{
    private string $inputMessageFormat;

    private array $inputReplacements = [];

    final public function getInputMessageFormat(): ?string
    {
        return $this->inputMessageFormat;
    }

    final public function getInputReplacements(): array
    {
        return $this->inputReplacements;
    }

    /**
     * Handle "compilation" of the final previous exception by filtering the passed parameters for instances of \Throwable
     * and returning the first instance found.
     */
    final protected function resolvePreviousException(array $parameters = []): ?\Throwable
    {
        return self::filterThrowable($parameters)[0] ?? null;
    }

    /**
     * Handle compilation of the final message using a string value and an optional array of replacements. This internal
     * function {@see vsprintf} is used, so reference it's documentation for acceptable anchor syntax of the
     * string. Failure of the {@see vsprintf} call (which happens when, for example, the message string contains a
     * different number of anchor than the number of replacements provided) will not fail or return null, but
     * instead return the message string in its un-compiled form.
     */
    final protected function resolveMessage(string $message = null, array $parameters = []): ?string
    {
        return (new StringInterpolator($this->inputMessageFormat = $message))(
            ...$this->inputReplacements = self::filterNotThrowable($parameters)
        );
    }

    /**
     * Filters an array of parameters (the values passed to any of this object's variadic methods) of all throwables.
     */
    private static function filterNotThrowable(array $parameters): array
    {
        return array_values(array_map(function ($v) {
            return (new StringTransformer())($v);
        }, array_filter($parameters, function ($p) {
            return !$p instanceof \Throwable;
        })));
    }

    /**
     * Filters an array of parameters (the values passed to any of this object's variadic methods) of non-throwables
     * and returns the first found or null if none are found.
     *
     * @return \Throwable[]
     */
    private static function filterThrowable(array $parameters): array
    {
        return array_values(array_filter($parameters, function ($p) {
            return $p instanceof \Throwable;
        }));
    }
}
