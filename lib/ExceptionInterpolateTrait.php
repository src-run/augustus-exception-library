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
use SR\Utilities\ClassQuery;

trait ExceptionInterpolateTrait
{
    /**
     * @var string
     */
    private $inputMessage;

    /**
     * @var mixed[]
     */
    private $inputReplacements = [];

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
    final protected function resolvePreviousException(array $parameters = [])
    {
        if (empty($thrown = self::filterThrowable($parameters))) {
            return null;
        }

        return array_shift($thrown);
    }

    /**
     * Handle compilation of the final message using a string value and an optional array of replacements. This internal
     * function {@see vsprintf} is used, so reference it's documentation for acceptable placeholder syntax of the
     * string. Failure of the {@see vsprintf} call (which happens when, for example, the message string contains a
     * different number of placeholder than the number of replacements provided) will not fail or return null, but
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
        $this->inputReplacements = $replacements = self::filterNotThrowable($parameters);

        return self::interpolateMessage($message, $replacements, false)
            ?: self::interpolateMessage($message, $replacements, true);
    }

    /**
     * Try to compile message with provided string and replacement array.
     *
     * @param string  $message                 The message string, which may contain placeholders for vsprintf
     * @param mixed[] $replacements            Array of replacements for the string
     * @param bool    $removeExtraPlaceholders If true extra placeholders will be removed from the string such that the
     *                                         number of placeholders matches the number of replacements
     *
     * @return null|string
     */
    final private static function interpolateMessage(string $message, array $replacements, bool $removeExtraPlaceholders): ?string
    {
        if (true === $removeExtraPlaceholders) {
            $message = self::removeExtraPlaceholders($message, count($replacements));
        }

        $result = CallSilencerFactory::create(function () use ($message, $replacements) {
            return vsprintf($message, $replacements);
        }, function ($return) {
            return false !== $return && null !== $return && true !== empty($return);
        })->invoke();

        return $result->isValid() ? $result->getReturn() : null;
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
        return array_map(function ($v) {
            return self::stringifyValue($v);
        }, array_filter($parameters, function ($p) {
            return !ClassQuery::isThrowableEquitable($p);
        }));
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
        return array_filter($parameters, function ($p) {
            return ClassQuery::isThrowableEquitable($p);
        });
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
     * Replaces the message's replacement placeholders (used by {@see compileMessage()} (beginning with the nth found,
     * as defined by the startAt parameter) with a type representation of the expected value.
     *
     * @param string $message
     * @param int    $startAt
     *
     * @return string
     */
    final private static function removeExtraPlaceholders(string $message, int $startAt = 0): string
    {
        $regex = '{%([0-9-]+)?([sducoxXbgGeEfF])([0-9]?(?:\$[0-9]?[0-9]?[a-zA-Z]?)?)}';
        $count = 0;

        return preg_replace_callback($regex, function ($match) use ($startAt, &$count) {
            return ++$count > $startAt ? sprintf('<%s:null>', self::describePlaceholder($match[2])) : $match[0];
        }, $message);
    }

    /**
     * Expand placeholder (such as %s or %d) used in message to its full type name (such as string or integer).
     *
     * @param string $placeholder
     *
     * @return string
     */
    final private static function describePlaceholder(string $placeholder): string
    {
        $maps = [
            'string' => ['s'],
            'integer' => ['d', 'u', 'c', 'o', 'x', 'X', 'b'],
            'double' => ['g', 'G', 'e', 'E', 'f', 'F'],
        ];

        foreach ($maps as $name => $chars) {
            if (in_array($placeholder, $chars, true)) {
                $description = $name;
            }
        }

        return $description ?? 'unknown';
    }
}
