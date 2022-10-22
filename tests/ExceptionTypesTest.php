<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception\Tests;

use PHPUnit\Framework\TestCase;
use SR\Exception\Exception;
use SR\Exception\Tests\Fixtures\ExceptionTypes;
use SR\Utilities\Context\FileContextInterface;
use SR\Utilities\Query\ClassQuery;

/**
 * @covers \SR\Exception\Exception
 * @covers \SR\Exception\Logic\BadFunctionCallException
 * @covers \SR\Exception\Logic\BadMethodCallException
 * @covers \SR\Exception\Logic\DomainException
 * @covers \SR\Exception\Logic\InvalidArgumentException
 * @covers \SR\Exception\Logic\LengthException
 * @covers \SR\Exception\Logic\LogicException
 * @covers \SR\Exception\Logic\OutOfRangeException
 * @covers \SR\Exception\Runtime\OutOfBoundsException
 * @covers \SR\Exception\Runtime\OverflowException
 * @covers \SR\Exception\Runtime\RangeException
 * @covers \SR\Exception\Runtime\RuntimeException
 * @covers \SR\Exception\Runtime\UnderflowException
 * @covers \SR\Exception\Runtime\UnexpectedValueException
 * @covers \SR\Exception\Utility\Interpolator\StringInterpolator
 * @covers \SR\Exception\Utility\Dumper\Transformer\StringTransformer
 */
class ExceptionTypesTest extends TestCase
{
    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return ExceptionTypes::getExceptionClasses(function (string $name): array {
            return [$name];
        });
    }

    /**
     * @dataProvider getTypes
     *
     * @param string $type
     */
    public function testBasicFunctionality($type): void
    {
        $p = $this->getException(Exception::class);
        $e = $this->getException($type, $p);

        $this->assertNotNull($e->getMessage());
        $this->assertNotNull($e->getCode());
        $this->assertNotNull($e->getLine());
        $this->assertInstanceOf(FileContextInterface::class, $e->getContext());
        $this->assertSame(__FILE__, $e->getFile());
        $this->assertSame(__FILE__, $e->getContext()->getFile()->getPathname());
        $this->assertSame(__CLASS__, $e->getContextClassName());
        $this->assertSame(ClassQuery::getNameShort(__CLASS__), $e->getContextClassName(false));
        $this->assertSame('getException', $e->getContextMethodName());
        $this->assertSame(__CLASS__ . '::getException', $e->getContextMethodName(true));
        $this->assertSame($p, $e->getPrevious());

        $e = $this->getExceptionStatic($type, $p);
        $this->assertSame(__FILE__, $e->getFile());
        $this->assertSame(__FILE__, $e->getContext()->getFile()->getPathname());
        $this->assertSame(__CLASS__, $e->getContextClassName());
        $this->assertSame(ClassQuery::getNameShort(__CLASS__), $e->getContextClassName(false));
        $this->assertSame('getExceptionStatic', $e->getContextMethodName());
        $this->assertSame(__CLASS__ . '::getExceptionStatic', $e->getContextMethodName(true));
        $this->assertSame($p, $e->getPrevious());
    }

    /**
     * @param mixed[] $replace
     *
     * @return Exception
     */
    private function getException(string $type, \Exception $previous = null, string $message = 'Exception message', array $replace = []): \Exception
    {
        return new $type($message, ...array_merge($replace, array_filter([$previous])));
    }

    /**
     * @return Exception
     */
    private function getExceptionStatic(string $type, \Exception $previous): \Exception
    {
        return call_user_func(
            sprintf('%s::create', $type), 'Message for static created exception', $previous
        );
    }
}
