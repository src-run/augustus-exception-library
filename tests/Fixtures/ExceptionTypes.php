<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/** @noinspection PhpInconsistentReturnPointsInspection */

/**
 * Created by PhpStorm.
 * User: rmf
 * Date: 3/1/19
 * Time: 4:27 AM
 */

namespace SR\Exception\Tests\Fixtures;

use PHPUnit\Framework\Assert;
use SR\Exception\Exception;
use SR\Exception\ExceptionInterface;
use SR\Exception\Logic\BadFunctionCallException;
use SR\Exception\Logic\BadMethodCallException;
use SR\Exception\Logic\DomainException;
use SR\Exception\Logic\InvalidArgumentException;
use SR\Exception\Logic\LengthException;
use SR\Exception\Logic\LogicException;
use SR\Exception\Logic\OutOfRangeException;
use SR\Exception\Runtime\OutOfBoundsException;
use SR\Exception\Runtime\OverflowException;
use SR\Exception\Runtime\RangeException;
use SR\Exception\Runtime\RuntimeException;
use SR\Exception\Runtime\UnderflowException;
use SR\Exception\Runtime\UnexpectedValueException;
use SR\Utilities\Query\ClassQuery;

/**
 * @coversNothing
 */
class ExceptionTypes
{
    /**
     * @var string[]
     */
    private const EXCEPTION_CLASSES = [
        BadFunctionCallException::class,
        BadMethodCallException::class,
        DomainException::class,
        InvalidArgumentException::class,
        LengthException::class,
        LogicException::class,
        OutOfRangeException::class,
        OutOfBoundsException::class,
        OverflowException::class,
        RangeException::class,
        RuntimeException::class,
        UnderflowException::class,
        UnexpectedValueException::class,
        Exception::class,
    ];

    /**
     * @return string[]
     */
    public static function getExceptionClasses(\Closure $mapper = null, \Closure $filter = null): array
    {
        return array_values(array_filter(array_map($mapper ?? function (string $name): string {
            return $name;
        }, self::EXCEPTION_CLASSES), $filter ?? function () {
            return true;
        }));
    }

    public static function getRandomExceptionClass(\Closure $mapper = null, \Closure $filter = null): string
    {
        try {
            return ($types = self::getExceptionClasses($mapper, $filter))[random_int(0, count($types) - 1)];
        } catch (\Exception $e) {
            Assert::fail(sprintf(
                'Failed to get one random exception type class name: "%s".', $e->getMessage()
            ));
        }
    }

    /**
     * @param string|object $thrownContext
     *
     * @throws ExceptionInterface
     */
    public static function throwRandomException($thrownContext): void
    {
        throw self::createRandomException($thrownContext);
    }

    /**
     * @param string|object $thrownContext
     */
    public static function createRandomException($thrownContext, bool $staticConstructor = false): ExceptionInterface
    {
        $name = self::getRandomExceptionClass();
        $args = self::getDefaultExceptionArguments($thrownContext, $name);

        return $staticConstructor
            ? $name::create(...$args)
            : new $name(...$args);
    }

    /**
     * @param string|object $thrownContext
     */
    public static function getRandExceptionCreationClosure($thrownContext, bool $staticConstructor = false): \Closure
    {
        $name = self::getRandomExceptionClass();
        $args = self::getDefaultExceptionArguments($thrownContext, $name);

        return function () use ($name, $args, $staticConstructor) {
            return $staticConstructor
                ? $name::create(...$args)
                : new $name(...$args);
        };
    }

    /**
     * @param string|object      $thrownContext
     * @param string|object|null $exceptionContext
     */
    public static function getDefaultExceptionArguments($thrownContext, $exceptionContext = null): array
    {
        return [
            'A exception (of type "%s" as defined in "%s") was thrown from "%s" with a message format that was defined in "%s()" (on line %d of "%s")...',
            self::normalizeContext($exceptionContext),
            self::locateFileContext($exceptionContext),
            self::normalizeContext($thrownContext, true),
            __METHOD__,
            __FILE__,
            __LINE__,
        ];
    }

    /**
     * @param string|object|null $context
     */
    private static function locateFileContext($context = null): string
    {
        return ClassQuery::getReflection(self::normalizeContext($context))->getFileName();
    }

    /**
     * @param string|object|null $context
     */
    private static function normalizeContext($context = null, bool $useFunc = false): string
    {
        if (null === $context) {
            return ExceptionInterface::class;
        }

        if (!is_string($context = is_object($context) ? ClassQuery::getNameQualified($context) : $context)) {
            Assert::fail(sprintf(
                'Failed to normalize "%s" context (expected "string"): %s', gettype($context), var_export($context, true)
            ));
        }

        @preg_match('{(?<object>.+)::(?<method>.+)}', $context, $matched);

        $object = $matched['object'] ?? $context;
        $method = $matched['method'] ?? null;

        return false === $useFunc || null === $method
            ? $object
            : sprintf('%s::%s', $object, $method);
    }
}
