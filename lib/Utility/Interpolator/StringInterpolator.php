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
use SR\Utilities\Characters\AsciiCharacters;

final class StringInterpolator
{
    /**
     * @var string|null
     */
    private $format;

    /**
     * @var string[]
     */
    private static $specificationRegexRules = [
        '([0-9]{1,}\$)?',    // An optional "argument swapping id"
        '([+-])?',           // An optional "sign signifier"
        '(\'.{0,1}|[0\s])?', // An optional "padding specifier"
        '(-?[0-9]+)?',       // An optional "alignment specifier" and "width specifier"
        '(\.[0-9]+)?',       // An optional "precision specifier"
    ];

    /**
     * @var array[]
     */
    private static $specificationTypeData = [
        'b' => ['expected' => 'integer', 'provided' => 'binary number', 'modifier' => null],
        'c' => ['expected' => 'integer', 'provided' => 'ascii character', 'modifier' => null],
        'd' => ['expected' => 'integer', 'provided' => 'decimal number', 'modifier' => 'signed'],
        'e' => ['expected' => 'scientific notation', 'provided' => 'scientific notation', 'modifier' => 'lowercase'],
        'E' => ['expected' => 'scientific notation', 'provided' => 'scientific notation', 'modifier' => 'uppercase'],
        'f' => ['expected' => 'float', 'provided' => 'floating-point number', 'modifier' => 'locale aware'],
        'F' => ['expected' => 'float', 'provided' => 'floating-point number', 'modifier' => 'non-locale aware'],
        'g' => ['expected' => ['float', 'scientific notation'], 'provided' => ['float', 'scientific notation'], 'modifier' => 'whichever is shortest'],
        'G' => ['expected' => ['float', 'scientific notation'], 'provided' => ['float', 'scientific notation'], 'modifier' => 'whichever is shortest'],
        'o' => ['expected' => 'integer', 'provided' => 'octal number', 'modifier' => null],
        's' => ['expected' => 'string', 'provided' => 'string', 'modifier' => null],
        'u' => ['expected' => 'integer', 'provided' => 'decimal number', 'modifier' => 'unsigned'],
        'x' => ['expected' => 'integer', 'provided' => 'hexadecimal number', 'modifier' => 'lowercase'],
        'X' => ['expected' => 'integer', 'provided' => 'hexadecimal number', 'modifier' => 'uppercase'],
    ];

    /**
     * @param string|null $format
     */
    public function __construct(string $format = null)
    {
        $this->format = self::escapeUnknownSpecificationTypes($format);
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

        $this->format = preg_replace_callback(self::getValidSpecificationSyntaxRegex(), function (array $match) use ($start, &$count): string {
            if (self::isValidSwappedSpecification($match[0], $start)) {
                return $match[0];
            }

            if (++$count > $start || self::isExtraSwappedSpecification($match[0], $start)) {
                return self::buildSpecificationTypeDesc($match['type']);
            }

            return $match[0];
        }, $this->format);

        return $this;
    }

    /**
     * @param string|null $format
     *
     * @return string
     */
    private static function escapeUnknownSpecificationTypes(string $format = null): string
    {
        return null === $format ? null : preg_replace_callback(
            self::getUnknownSpecificationSyntaxRegex(), function (array $match) {
                return sprintf('%%%s', $match['match']);
            }, $format
        );
    }

    /**
     * @param string $specification
     * @param int    $count
     *
     * @return bool
     */
    private static function isExtraSwappedSpecification(string $specification, int $count): bool
    {
        return 1 === preg_match('/^%\d+\$/', $specification) && !self::isValidSwappedSpecification($specification, $count);
    }

    /**
     * @param string $specification
     * @param int    $count
     *
     * @return bool
     */
    private static function isValidSwappedSpecification(string $specification, int $count): bool
    {
        return $count > 0 && 1 === preg_match(sprintf('/^%%(%s)\$/', implode('|', range(1, $count))), $specification);
    }

    /**
     * Expand anchor (such as %s or %d) used in message to its full type name (such as string or integer).
     *
     * @param string $type
     *
     * @return string
     */
    private static function buildSpecificationTypeDesc(string $type): string
    {
        $expected = self::buildSpecificationTypeDescData($type, 'expected', '%s', '') ?: 'undefined-foo';
        $provided = self::buildSpecificationTypeDescData($type, 'provided', '%s', '') ?: 'undefined-bar';
        $modifier = self::buildSpecificationTypeDescData($type, 'modifier', ' (%s)', '', ', ');

        return $expected === $provided
            ? sprintf('{{ %%%%%s: %s%s }}', $type, $expected, $modifier)
            : sprintf('{{ %%%%%s: %s => %s%s }}', $type, $expected, $provided, $modifier);
    }

    /**
     * @param string      $type
     * @param string      $what
     * @param string      $format
     * @param string|null $join
     * @param string|null $default
     *
     * @return string
     */
    private static function buildSpecificationTypeDescData(string $type, string $what, string $format, string $default, string $join = null): string
    {
        return empty($data = (array) ((self::$specificationTypeData[$type] ?? [])[$what] ?? []))
            ? $default
            : sprintf($format, implode($join ?? ' or ', $data));
    }

    /**
     * @return string
     */
    private static function getValidSpecificationSyntaxRegex(): string
    {
        return vsprintf('/%%%s(?<type>[%s]{1})/', [
            implode('', self::$specificationRegexRules),
            implode('', self::getSpecificationTypes()),
        ]);
    }

    /**
     * @return string
     */
    private static function getUnknownSpecificationSyntaxRegex(): string
    {
        return vsprintf('/(?<match>%%%s(?<type>[%s]{1}))/', [
            implode('', self::$specificationRegexRules),
            implode('', array_filter((new AsciiCharacters())->letters()->chars(), function (string $c): bool {
                return !in_array($c, self::getSpecificationTypes(), true);
            })),
        ]);
    }

    /**
     * @return string[]
     */
    private static function getSpecificationTypes(): array
    {
        return array_keys(self::$specificationTypeData);
    }
}
