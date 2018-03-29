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
use SR\Utilities\ClassQuery;

/**
 * @covers \SR\Exception\Exception
 * @covers \SR\Exception\ExceptionAttributesTrait
 * @covers \SR\Exception\ExceptionContextTrait
 * @covers \SR\Exception\ExceptionInterface
 * @covers \SR\Exception\ExceptionInterpolateTrait
 * @covers \SR\Exception\ExceptionTrait
 */
class ExceptionTest extends TestCase
{
    public function testGetInput()
    {
        $m = 'A simple %s with %d replacements.';
        $r = ['string', 2];
        $i = ['string', '2'];

        $e1 = $this->getException($m, $r);
        $this->assertSame($m, $e1->getInputMessage());
        $this->assertSame($i, $e1->getInputReplacements());

        $e2 = $this->getException($m, array_merge($r, [$e1]));
        $this->assertSame($m, $e2->getInputMessage());
        $this->assertSame($i, $e2->getInputReplacements());
    }

    public function testGetMethodAndClassContext()
    {
        $exception = $this->getException();
        $this->assertSame('getException', $exception->getContextMethod()->getShortName());
        $this->assertSame('getException', $exception->getContextMethodName());
        $this->assertSame(__CLASS__.'::getException', $exception->getContextMethodName(true));
        $this->assertSame(__CLASS__, $exception->getContextClass()->getName());
        $this->assertSame(__CLASS__, $exception->getContextClassName());
        $this->assertSame(ClassQuery::getNameShort(__CLASS__), $exception->getContextClassName(false));
    }

    public function testFileDiff()
    {
        $exception = $this->getException('A %s.', ['message']);
        $this->assertCount(7, $exception->getContextFileSnippet(3));
        $this->assertTrue(in_array(
            '    private function getException($message = \'A test exception\', array $replacements = [])',
            $exception->getContextFileSnippet(10))
        );
    }

    public function testDefaults()
    {
        $exception = $this->getException('A %s.', ['message']);
        $this->assertSame('A message.', $exception->getMessage());

        $exception = $this->getException();
        $this->assertNotNull($exception->getMessage());
        $this->assertNotNull($exception->getCode());
    }

    public function testToString()
    {
        $exception = $this->getException();
        $attributes = [
            'index-01' => 'value-01',
            'index-02' => 'value-02',
        ];

        $this->assertRegExp('{Exception: A test exception \(in "[^"]+" at "[^"]+"}', $exception->__toString());

        foreach ($attributes as $i => $a) {
            $exception->setAttribute($i, $a);
        }
        $this->assertRegExp('{Attributes: \[index-01\]=value-01, \[index-02\]=value-02}', $exception->__toString());
    }

    public function testStringifyComplex()
    {
        $a = new class() {
            private $inner;

            public function setInner($inner)
            {
                $this->inner = $inner;
            }
        };

        $b = new class() {
            private $inner;

            public function setInner($inner)
            {
                $this->inner = $inner;
            }
        };

        $c = new class() {
            public function __toString(): string
            {
                return 'abcdef0123';
            }
        };

        $b->setInner($a);
        $a->setInner($b);

        $e = $this->getException('Complex stringify replacements like "%s" and "%s" and "%s"', [$a, [$a, $b], $c]);
        $this->assertNotNull($e->getMessage());
        $this->assertStringMatchesFormat('%sabcdef0123%s', $e->getMessage());
    }

    public function testType()
    {
        $this->assertRegExp('{^Exception$}', $this->getException()->getType());
        $this->assertRegExp('{^SR\\\}', $this->getException()->getType(true));
    }

    public function testTypeQualified()
    {
        $exception = $this->getException();

        $this->assertNotRegExp('{^Exception$}', $exception->getType(true));
        $this->assertRegExp('{Exception$}', $exception->getType(true));
    }

    public function testCompileMessage()
    {
        $exception = $this->getException('A %s with number: "%d"', ['string', 10]);
        $this->assertSame('A string with number: "10"', $exception->getMessage());

        $exception = $this->getException('Second string with number: "%d"', [100]);
        $this->assertSame('Second string with number: "100"', $exception->getMessage());

        $exception = $this->getException('Second string with number "%04d" and undefined string "%s"', [100]);
        $this->assertSame('Second string with number "0100" and undefined string "<string:null>"', $exception->getMessage());

        $exception = $this->getException('Second string with undefined number "%04d" and undefined string "%s"');
        $this->assertSame('Second string with undefined number "<integer:null>" and undefined string "<string:null>"', $exception->getMessage());

        $exception = $this->getException('Second string with number "%d" and string "%s"', [1, 'bar', 'foo-bar']);
        $this->assertSame('Second string with number "1" and string "bar"', $exception->getMessage());
    }

    public function testCreate()
    {
        $exception = Exception::create();

        $this->assertRegExp('{ExceptionTest.php$}', $exception->getFile());
    }

    public function testToArray()
    {
        $exception = new Exception();
        $exported = $exception->__toArray();

        foreach (['type', 'message', 'code', 'class', 'method', 'file-name', 'file-line', 'file-diff', 'attributes', 'traceable'] as $key) {
            $this->assertArrayHasKey($key, $exported);
        }

        $this->assertSame($exception->getTrace(), $exported['traceable']());
    }

    public function testPrevious()
    {
        $exception = new Exception('', $previous = new Exception());

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testAttributes()
    {
        $exception = new Exception('', $previous = new Exception());
        $attributes = [
            'index-01' => 'value-01',
            'index-02' => 'value-02',
        ];

        foreach ($attributes as $i => $v) {
            $this->assertFalse($exception->hasAttribute($i));
            $exception->setAttribute($i, $v);
            $this->assertSame($v, $exception->getAttribute($i));
            $this->assertTrue($exception->hasAttribute($i));
        }
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Exception
     */
    public function getExceptionMock()
    {
        return $this->getMockBuilder(Exception::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testToStringExceptionNotThrownOnBadContext()
    {
        $exception = $this->getExceptionMock();
        $exception->__construct('Mock exception');

        $reflection = new \ReflectionObject($exception);
        $property = $reflection->getProperty('file');
        $property->setAccessible(true);
        $property->setValue($exception, realpath(__DIR__.'/Fixtures/NoClass.php'));

        $this->assertNull($exception->getContextClass());
        $this->assertNull($exception->getContextClassName());
        $this->assertNull($exception->getContextMethod());
        $this->assertNull($exception->getContextMethodName());
        $this->assertCount(0, $exception->getContextFileSnippet());
    }

    /**
     * @param string $message
     * @param array  $replacements
     *
     * @return Exception
     */
    private function getException($message = 'A test exception', array $replacements = [])
    {
        return new Exception($message, ...$replacements);
    }
}
