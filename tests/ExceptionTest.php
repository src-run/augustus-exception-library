<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 * (c) Scribe Inc      <scr@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception\Tests;

use SR\Exception\BadFunctionCallException;
use SR\Exception\Exception;
use SR\Exception\ExceptionInterface;
use SR\Exception\InvalidArgumentException;
use SR\Exception\LogicException;
use SR\Exception\RuntimeException;

/**
 * Class ExceptionTest.
 */
class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $e = new Exception('A %s.', 'message');
        static::assertEquals('A message.', $e->getMessage());
        static::assertEquals(ExceptionInterface::CODE_GENERIC, $e->getCode());

        $e = new Exception();
        static::assertNotNull($e->getMessage());
        static::assertNotNull($e->getCode());
    }

    public function testPrevious()
    {
        $p = new Exception();
        $e = new Exception(null, $p);

        static::assertArraySubset(['back' => []], $e->__debugInfo());
        static::assertSame($p, $e->getPrevious());
    }

    public function testMessageReplacementPlaceholderCleanup()
    {
        $e = new Exception('A message with (%s) unused placeholders %s everywhere %d$01d and %s$s');

        static::assertSame('A message with (<null>) unused placeholders <null> everywhere <null> and <null>', $e->getMessage());
    }

    public function testLogicException()
    {
        $e = new LogicException('A %s.', 'message');
        static::assertEquals('A message.', $e->getMessage());
        static::assertEquals(ExceptionInterface::CODE_GENERIC, $e->getCode());

        $e = new LogicException();
        static::assertNotNull($e->getMessage());
        static::assertNotNull($e->getCode());
    }

    public function testBadFunctionCallException()
    {
        $e = new BadFunctionCallException('A %s.', 'message');
        static::assertEquals('A message.', $e->getMessage());
        static::assertEquals(ExceptionInterface::CODE_GENERIC, $e->getCode());

        $e = new BadFunctionCallException();
        static::assertNotNull($e->getMessage());
        static::assertNotNull($e->getCode());
    }

    public function testRuntimeException()
    {
        $e = new RuntimeException('A %s.', 'message');
        static::assertEquals('A message.', $e->getMessage());

        $e = new RuntimeException();
        static::assertNotNull($e->getMessage());
        static::assertNotNull($e->getCode());
    }

    public function testInvalidArgumentException()
    {
        $e = new InvalidArgumentException('A %s.', 'message');
        static::assertEquals('A message.', $e->getMessage());

        $e = new InvalidArgumentException();
        static::assertNotNull($e->getMessage());
        static::assertNotNull($e->getCode());
    }
}

/* EOF */
