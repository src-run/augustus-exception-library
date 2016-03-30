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

use SR\Exception\ExceptionInterface;
use SR\Exception\ExceptionTrait;
use SR\Exception\LogicException;

/**
 * Class ExceptionTraitTest.
 */
class ExceptionTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExceptionTrait|ExceptionInterface
     */
    protected static $e;

    /**
     * @return ExceptionTrait|ExceptionInterface
     */
    public function setUp()
    {
        static::$e = $this->makeMock();

        parent::setUp();
    }

    /**
     * @return ExceptionTrait|ExceptionInterface
     */
    public function makeMock()
    {
        return $this->getMockBuilder('SR\Exception\ExceptionTrait')
            ->disableOriginalConstructor()
            ->getMockForTrait();
    }

    /**
     * @param string $message
     * @param mixed  $parameter
     *
     * @return LogicException
     */
    public function makeMockForLogicExcepeption($message, $parameter)
    {
        return new LogicException($message, $parameter);
    }

    public function testToString()
    {
        static::assertStringStartsWith('Array', static::$e->__toString());
    }

    public function testDebugOutput()
    {
        static::assertArraySubset(['back' => []], static::$e->__debugInfo());
    }

    public function testType()
    {
        static::assertRegExp('{^Mock_Trait_ExceptionTrait_.+}', static::$e->getType());
    }

    public function testGetMessageSprintf()
    {
        $e = $this->makeMockForLogicExcepeption(null, null);
        $e->setMessage('A %s string with number "%d".', 'test', 10);
        static::assertEquals('A test string with number "10".', $e->getMessage());
    }

    public function testMessageInConstructorAndWithForParameters()
    {
        $previous = new \Exception();
        $string = 'A test %s with %d number.';

        $e = LogicException::create($string)->with($previous, 'string', 10);

        static::assertEquals('A test string with 10 number.', $e->getMessage());
    }

    public function testSetAttributes()
    {
        $a = [
            'index-string' => 'value 01',
            'numeric index',
        ];

        static::$e->setAttributes($a);
        static::assertEquals($a, static::$e->getAttributes());
    }

    public function testAddAttribute()
    {
        $a = ['index-string' => 'value 01'];
        $b = ['numeric-index-value'];

        static::$e->addAttribute(current($a), key($a));
        static::assertEquals($a, static::$e->getAttributes());
        static::$e->addAttribute(current($b));
        static::assertEquals(array_merge($a, $b), static::$e->getAttributes());
        static::assertTrue(static::$e->hasAttribute(key($a)));
        static::assertSame(current($a), static::$e->getAttribute(key($a)));
        static::assertFalse(static::$e->hasAttribute('invalid-attribute'));
        static::assertNull(static::$e->getAttribute('invalid-attribute'));
    }

    public function testCodeAndMessageDefaults()
    {
        $e = static::$e;

        static::assertSame(ExceptionInterface::CODE_GENERIC, $e->getDefaultCode());
        static::assertSame(ExceptionInterface::MSG_GENERIC, $e->getDefaultMessage());
    }

    public function testSetterAndGetterMethods()
    {
        $e = $this->makeMockForLogicExcepeption('Custom string %s', __METHOD__);
        static::assertSame(sprintf('Custom string %s', __METHOD__), $e->getMessage());

        $e = $this->makeMockForLogicExcepeption(null, 'idk');

        $message = 'A brand new message for the test method %s.';
        $method = __METHOD__;
        $code = mt_rand(100,999);
        $file = __FILE__;
        $line = __LINE__;

        $e
            ->setMessage($message, $method)
            ->setCode($code)
            ->setFile($file)
            ->setLine($line);

        static::assertEquals(sprintf($message, $method), $e->getMessage());
        static::assertEquals($code, $e->getCode());
        static::assertEquals($line, $e->getLine());
        static::assertEquals($file, $e->getFile());

        $e2 = LogicException::create('This one from %s test case has %d exceptions passed with replacements.', __METHOD__, 1, $e);

        static::assertSame($e, $e2->getPrevious());

        $e->setPrevious($e2);

        static::assertSame($e2, $e->getPrevious());

        $splFile = new \SplFileInfo(__FILE__);
        $e->setFile($splFile);

        static::assertEquals($splFile->getPathname(), $e->getFile());
    }
}

/* EOF */
