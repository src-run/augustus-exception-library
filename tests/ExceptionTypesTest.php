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
use SR\Utilities\Context\FileContextInterface;

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
 */
class ExceptionTypesTest extends TestCase
{
    /**
     * @return string[]
     */
    public function getTypes()
    {
        return [
            [BadFunctionCallException::class],
            [BadMethodCallException::class],
            [DomainException::class],
            [InvalidArgumentException::class],
            [LengthException::class],
            [LogicException::class],
            [OutOfRangeException::class],
            [OutOfBoundsException::class],
            [OverflowException::class],
            [RangeException::class],
            [RuntimeException::class],
            [UnderflowException::class],
            [UnexpectedValueException::class],
            [Exception::class],
        ];
    }

    /**
     * @dataProvider getTypes
     *
     * @param string $type
     */
    public function testBasicFunctionality($type)
    {
        $previous = $this->getException(Exception::class);
        $exception = $this->getException($type, $previous);

        $this->assertNotNull($exception->getMessage());
        $this->assertNotNull($exception->getCode());
        $this->assertNotNull($exception->getLine());
        $this->assertInstanceOf(FileContextInterface::class, $exception->getContext());
        $this->assertSame(__FILE__, $exception->getFile());
        $this->assertSame(__FILE__, $exception->getContext()->getFile()->getPathname());
        $this->assertSame(__CLASS__, $exception->getContextClass());
        $this->assertSame(__CLASS__.'::getException', $exception->getContextMethod());
        $this->assertSame($previous, $exception->getPrevious());

        $exception = $this->getExceptionStatic($type, $previous);
        $this->assertSame(__FILE__, $exception->getFile());
        $this->assertSame(__FILE__, $exception->getContext()->getFile()->getPathname());
        $this->assertSame(__CLASS__, $exception->getContextClass());
        $this->assertSame(__CLASS__.'::getExceptionStatic', $exception->getContextMethod());
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * @param string $type
     * @param string $message
     * @param array  $replace
     *
     * @return Exception
     */
    private function getException(string $type, \Exception $previous = null, string $message = 'Exception message', array $replace = []): \Exception
    {
        return new $type($message, ...array_merge($replace, $previous ? [$previous] : []));
    }

    /**
     * @param string     $type
     * @param \Exception $previous
     *
     * @return Exception
     */
    private function getExceptionStatic(string $type, \Exception $previous): \Exception
    {
        return call_user_func_array($type.'::create', ['Message for static created exception', $previous]);
    }
}

/* EOF */
