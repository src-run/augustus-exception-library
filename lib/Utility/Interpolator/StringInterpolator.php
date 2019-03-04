<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception\Utility\Interpolator;

use SR\Silencer\CallSilencerFactory;

final class StringInterpolator
{
    /**
     * @var string|null
     */
    private $format;

    /**
     * @var string
     */
    private static $conversionSpecificationRegex;

    /**
     * @var string[]
     */
    private static $conversionSpecificationRules = [
        '([0-9]{1,}\$)?',    // An optional "argument swapping id"
        '([+-])?',           // An optional "sign signifier"
        '(\'.{0,1}|[0\s])?', // An optional "padding specifier"
        '(-?[0-9]+)?',       // An optional "alignment specifier" and "width specifier"
        '(\.[0-9]+)?',       // An optional "precision specifier"
    ];

    /**
     * @var string[]
     */
    private static $conversionSpecificationTypes = [
        'b' => 'expected an "integer" to be presented as a "binary number"',
        'c' => 'expected an "integer" to be presented as a "its ASCII character representation"',
        'd' => 'expected an "integer" to be presented as a "decimal number" (signed)',
        'e' => 'expected "scientific notation" to be presented as "scientific notation" (lowercase)',
        'E' => 'expected "scientific notation" to be presented as "scientific notation" (uppercase)',
        'f' => 'expected a "float" to be presented as a "floating-point number" (locale aware)',
        'F' => 'expected a "float" to be presented as a "floating-point number" (non-locale aware)',
        'g' => 'expected a "float or scientific notation" to be presented as the shorter of the two',
        'G' => 'expected a "float or scientific notation" to be presented as the shorter of the two',
        'o' => 'expected an "integer" to be presented as an "octal number"',
        's' => 'expected a "string" to be presented as a "string"',
        'u' => 'expected an "integer" to be presented as an "unsigned decimal number"',
        'x' => 'expected an "integer" to be presented as a "hexadecimal number" (lowercase)',
        'X' => 'expected an "integer" to be presented as a "hexadecimal number" (uppercase)',
    ];

    /**
     * @param string|null $format
     */
    public function __construct(string $format = null)
    {
        $this->format = $format;
    }

    /**
     * @param mixed ...$replacements
     *
     * @return string|null
     */
    public function __invoke(...$replacements): ?string
    {
        return $this->tryInterpolationWithAllPassedTypes($replacements)
            ?? $this->tryInterpolationWithAvailableTypes($replacements, $this->format);
    }

    /**
     * @param mixed[]     $replacements
     * @param string|null $default
     *
     * @return string|null
     */
    private function tryInterpolationWithAllPassedTypes(array $replacements, string $default = null): ?string
    {
        return null !== $this->format
            ? self::interpolate($this->format, $replacements) ?? $default
            : null;
    }

    /**
     * @param mixed[]     $replacements
     * @param string|null $default
     *
     * @return string
     */
    private function tryInterpolationWithAvailableTypes(array $replacements, string $default = null): ?string
    {
        return $this
            ->removeExtraConversionSpecifications($replacements)
            ->tryInterpolationWithAllPassedTypes($replacements, $default);
    }

    /**
     * @param array $replacements
     *
     * @return self
     */
    private function removeExtraConversionSpecifications(array $replacements): self
    {
        $count = 0;
        $start = count($replacements);

        $this->format = preg_replace_callback(self::getConversionSpecificationRegex(), function ($match) use ($start, &$count) {
            if (self::isValidSwappedConversionSpecification($match[0], $start)) {
                return $match[0];
            }

            if (++$count > $start || self::isInvalidSwappedConversionSpecification($match[0], $start)) {
                return self::describeTypeSpecifier($match['type']);
            }

            return $match[0];
        }, $this->format);

        return $this;
    }

    /**
     * @param string $specification
     * @param int    $count
     *
     * @return bool
     */
    private static function isInvalidSwappedConversionSpecification(string $specification, int $count): bool
    {
        return 1 === preg_match('/^%\d+\$/', $specification) && !self::isValidSwappedConversionSpecification($specification, $count);
    }

    /**
     * @param string $specification
     * @param int    $count
     *
     * @return bool
     */
    private static function isValidSwappedConversionSpecification(string $specification, int $count): bool
    {
        return $count > 0 && 1 === preg_match(sprintf('/^%%(%s)\$/', implode('|', range(1, $count))), $specification);
    }

    /**
     * @param string $format
     * @param array  $replacements
     *
     * @return string|null
     */
    private static function interpolate(string $format, array $replacements): ?string
    {
        return CallSilencerFactory::create(function () use ($format, $replacements): ?string {
            return vsprintf($format, $replacements) ?: null;
        })->invoke()->getReturn();
    }

    /**
     * Expand anchor (such as %s or %d) used in message to its full type name (such as string or integer).
     *
     * @param string $type
     *
     * @return string
     */
    private static function describeTypeSpecifier(string $type): string
    {
        return sprintf(
            '[%%%%%s (%s)]', $type, self::$conversionSpecificationTypes[$type] ?? 'unknown specification type'
        );
    }

    /**
     * @return string
     */
    private static function getConversionSpecificationRegex(): string
    {
        return self::$conversionSpecificationRegex
            ?? self::$conversionSpecificationRegex = self::buildConversionSpecificationRegex();
    }

    /**
     * @return string
     */
    private static function buildConversionSpecificationRegex(): string
    {
        return vsprintf('/%%%s(?<type>[%s]{1})/', [
            implode('', self::$conversionSpecificationRules),
            implode('', array_keys(self::$conversionSpecificationTypes)),
        ]);
    }
}
