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

use SR\Silencer\CallSilencerFactory;
use SR\Utilities\Query\ClassQuery;

trait ExceptionInterpolateTrait
{
    /**
     * @var string
     */
    private $inputMessage;

    /**
     * @var mixed[]
     */
    private $inputReplace = [];

    /**
     * @return null|string
     */
    public function getInputMessage(): ?string
    {
        return $this->inputMessage;
    }

    /**
     * @return array
     */
    public function getInputReplacements(): array
    {
        return $this->inputReplace;
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
     * @param null|string $message
     * @param mixed[]     $parameters
     *
     * @return string|null
     */
    final protected function resolveMessage(string $message = null, array $parameters = [])
    {
        $this->inputMessage = $message;
        $this->inputReplace = $replace = self::filterNotThrowable($parameters);

        return self::interpolateMessage($message, $replace)
            ?? self::interpolateMessage(self::removeExtraAnchors($message, $replace), $replace, $message);
    }

    /**
     * Try to compile message with provided string and replacement array.
     *
     * @param string      $message The message string, which may contain anchors for vsprintf
     * @param mixed[]     $replace Array of replacements for the string
     * @param string|null $default Default return value if interpolation does not complete successfully.
     *
     * @return null|string
     */
    final private static function interpolateMessage(string $message, array $replace, string $default = null): ?string
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
    final private static function filterNotThrowable(array $parameters): array
    {
        return array_values(array_map(function ($v) {
            return self::stringifyValue($v);
        }, array_filter($parameters, function ($p) {
            return !ClassQuery::isThrowableEquitable($p);
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
    final private static function filterThrowable(array $parameters)
    {
        return array_values(array_filter($parameters, function ($p) {
            return ClassQuery::isThrowableEquitable($p);
        }));
    }

    /**
     * Returns a scalar representation of the passed value.
     *
     * @param mixed $value
     *
     * @return string
     */
    final private static function stringifyValue($value)
    {
        if (is_scalar($value) || method_exists($value, '__toString')) {
            return (string) $value;
        }

        return trim(preg_replace('{\s+}', ' ', preg_replace(
            '{\n[\s\t]*}', ' ', @var_export($value, true) ?? @print_r($value, true))
        ), ' ');
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
    final private static function removeExtraAnchors(string $message, array $parameters): string
    {
        $count = 0;
        $start = count($parameters);

        return preg_replace_callback(self::getAnchorSearchRegex(), function ($match) use ($start, &$count) {
            return ++$count > $start ? self::getAnchorTypeDescription($match['type']) : $match[0];
        }, $message);
    }

    /**
     * Expand anchor (such as %s or %d) used in message to its full type name (such as string or integer).
     *
     * @param string $anchor
     *
     * @return string
     */
    final private static function getAnchorTypeDescription(string $anchor): string
    {
        foreach (static::getAnchorTypeDefinitions() as $type => $characters) {
            if (in_array($anchor, $characters, true)) {
                $desc = sprintf('[undefined (%s)]', $type);
            }
        }

        return $desc ?? '[undefined]';
    }

    /**
     * @return array[]
     */
    final private static function getAnchorTypeDefinitions(): array
    {
        return [
            'string' => [
                's'
            ],
            'integer' => [
                'd',
                'u',
                'c',
                'o',
                'x',
                'X',
                'b'
            ],
            'double' => [
                'g',
                'G',
                'e',
                'E',
                'f',
                'F'
            ],
        ];
    }

    /**
     * @return string
     */
    final private static function getAnchorSearchRegex(): string
    {
        return sprintf(
            '{%%([0-9-]+)?(?<type>[%s])([0-9]?(?:\$[0-9]?[0-9]?[a-zA-Z]?)?)}',
            array_reduce(static::getAnchorTypeDefinitions(), function (string $all, array $types) {
                return $all.implode($types);
            }, '')
        );
    }
}
