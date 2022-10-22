<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception\Utility\Dumper\Transformer;

use SR\Dumper\Transformer\StringTransformer as BaseStringTransformer;

final class StringTransformer
{
    private static BaseStringTransformer $transformer;

    public function __invoke(mixed $value): string
    {
        return self::stringifyValue($value);
    }

    /**
     * Returns a scalar representation of the passed value.
     */
    public static function stringifyValue(mixed $value): string
    {
        if (is_scalar($value) || is_callable([$value, '__toString'])) {
            return (string) $value;
        }

        if (is_array($value)) {
            return self::stringifyArray($value);
        }

        return trim(preg_replace('{\s+}', ' ', preg_replace(
            '{\n[\s\t]*}', ' ', self::getStringTransformer()($value))
        ), ' ');
    }

    public static function stringifyArray(array $array, string $joinBy = null, string $format = null): string
    {
        return implode($joinBy ?? ', ', array_map(function ($element) use ($format): string {
            return sprintf($format ?? '"%s"', self::stringifyValue($element));
        }, $array));
    }

    private static function getStringTransformer(): BaseStringTransformer
    {
        return self::$transformer
            ?? self::$transformer = new BaseStringTransformer();
    }
}
