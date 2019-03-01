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
use SR\Silencer\CallSilencerFactory;

trait ExceptionInterpolateTrait
{
    /**
     * @var string
     */
    private $inputMessageFormat;

    /**
     * @var mixed[]
     */
    private $inputReplacements = [];

    /**
     * @var array[]
     */
    private static $anchorTypes = [
        'string' => [
            's',
        ],
        'integer' => [
            'd',
            'u',
            'c',
            'o',
            'x',
            'X',
            'b',
        ],
        'double' => [
            'g',
            'G',
            'e',
            'E',
            'f',
            'F',
        ],
    ];

    /**
     * @return string|null
     */
    final public function getInputMessageFormat(): ?string
    {
        return $this->inputMessageFormat;
    }

    /**
     * @return array
     */
    final public function getInputReplacements(): array
    {
        return $this->inputReplacements;
    }

    /**
     * Handle "compilation" of the final previous exception by filtering the passed parameters for instances of \Throwable
     * and returning the first instance found.
     *
     * @param mixed[] $parameters
     *
     * @return \Throwable|null
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
     *
     * @param string|null $message
     * @param mixed[]     $parameters
     *
     * @return string|null
     */
    final protected function resolveMessage(string $message = null, array $parameters = []): ?string
    {
        $this->inputMessageFormat = $message;
        $this->inputReplacements = $replace = self::filterNotThrowable($parameters);

        return self::interpolateMessage($message, $replace)
            ?? self::interpolateMessage(self::removeExtraAnchors($message, $replace), $replace, $message);
    }

    /**
     * Try to compile message with provided string and replacement array.
     *
     * @param string      $message The message string, which may contain anchors for vsprintf
     * @param mixed[]     $replace Array of replacements for the string
     * @param string|null $default default return value if interpolation does not complete successfully
     *
     * @return string|null
     */
    private static function interpolateMessage(string $message, array $replace, string $default = null): ?string
    {
        $result = CallSilencerFactory::create(function () use ($message, $replace) {
            return vsprintf($message, $replace);
        }, function ($return) {
            return false !== $return && null !== $return && true !== empty($return);
        })->invoke();

        return $result->isValid() ? $result->getReturn() : $default;
    }

    /**
     * Filters an array of parameters (the values passed to any of this object's variadic methods) of all throwables.
     *
     * @param mixed[] $parameters
     *
     * @return mixed[]
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
     * @param mixed[] $parameters
     *
     * @return \Throwable[]
     */
    private static function filterThrowable(array $parameters): array
    {
        return array_values(array_filter($parameters, function ($p) {
            return $p instanceof \Throwable;
        }));
    }

    /**
     * Replaces the message's replacement anchors (used by {@see compileMessage()} (beginning with the nth found,
     * as defined by the startAt parameter) with a type representation of the expected value.
     *
     * @param string $message
     * @param array  $parameters
     *
     * @return string
     */
    private static function removeExtraAnchors(string $message, array $parameters): string
    {
        $count = 0;
        $start = count($parameters);

        return preg_replace_callback(self::buildAnchorRegex(), function ($match) use ($start, &$count) {
            return ++$count > $start ? self::describeAnchor($match['type']) : $match[0];
        }, $message);
    }

    /**
     * Expand anchor (such as %s or %d) used in message to its full type name (such as string or integer).
     *
     * @param string $anchor
     *
     * @return string
     */
    private static function describeAnchor(string $anchor): string
    {
        foreach (static::$anchorTypes as $type => $characters) {
            if (in_array($anchor, $characters, true)) {
                $desc = sprintf('[undefined (%s)]', $type);
            }
        }

        return $desc ?? '[undefined]';
    }

    /**
     * @return string
     */
    private static function buildAnchorRegex(): string
    {
        return sprintf(
            '{%%([0-9-]+)?(?<type>[%s])([0-9]?(?:\$[0-9]?[0-9]?[a-zA-Z]?)?)}',
            array_reduce(static::$anchorTypes, function (string $all, array $types) {
                return sprintf('%s%s', $all, implode('', $types));
            }, '')
        );
    }
}
