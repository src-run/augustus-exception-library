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
use SR\Exception\ExceptionInterface;
use SR\Exception\Tests\Fixtures\ExceptionTypes;
use SR\Utilities\Query\ClassQuery;

/**
 * @covers \SR\Exception\Exception
 * @covers \SR\Exception\ExceptionAttributesTrait
 * @covers \SR\Exception\ExceptionContextTrait
 * @covers \SR\Exception\ExceptionInterface
 * @covers \SR\Exception\ExceptionInterpolateTrait
 * @covers \SR\Exception\ExceptionTrait
 * @covers \SR\Exception\Utility\Interpolator\StringInterpolator
 * @covers \SR\Exception\Utility\Dumper\Transformer\StringTransformer
 */
class ExceptionTest extends TestCase
{
    public function testGetInput(): void
    {
        $m = 'A simple %s with %d replacements.';
        $r = ['string', 2];
        $i = ['string', '2'];

        $e1 = $this->getExceptionUsingNewKeyword($m, $r);
        $this->assertSame($m, $e1->getInputMessageFormat());
        $this->assertSame($i, $e1->getInputReplacements());

        $e2 = $this->getExceptionUsingNewKeyword($m, array_merge($r, [$e1]));
        $this->assertSame($m, $e2->getInputMessageFormat());
        $this->assertSame($i, $e2->getInputReplacements());
    }

    public function testGetMethodAndClassContext(): void
    {
        $e = $this->getExceptionUsingNewKeyword();
        $this->assertSame('getExceptionUsingNewKeyword', $e->getContextMethod()->getShortName());
        $this->assertSame('getExceptionUsingNewKeyword', $e->getContextMethodName());
        $this->assertSame(__CLASS__.'::getExceptionUsingNewKeyword', $e->getContextMethodName(true));
        $this->assertSame(__CLASS__, $e->getContextClass()->getName());
        $this->assertSame(__CLASS__, $e->getContextClassName());
        $this->assertSame(ClassQuery::getNameShort(__CLASS__), $e->getContextClassName(false));
    }

    /**
     * @return \Generator
     */
    public static function provideFileDiffContextSize(): \Generator
    {
        $methods = [
            [ExceptionTypes::class, 'createRandomException', false],
            [ExceptionTypes::class, 'createRandomException', true],
        ];

        foreach ($methods as [$class, $method, $static]) {
            foreach (range(1, self::getMethodMinSourceLinesFromStartOrUntilEnd($class, $method)) as $lineCount) {
                yield [$class, $method, $static, $lineCount];
            }
        }
    }

    /**
     * @dataProvider provideFileDiffContextSize
     *
     * @param string $class
     * @param string $method
     * @param bool   $static
     * @param int    $lines
     */
    public function testFileDiffContext(string $class, string $method, bool $static, int $lines): void
    {
        $e = call_user_func(sprintf('%s::%s', $class, $method), $this, $static);

        $this->assertContains(count($e->getContextFileSnippet($lines)), [
            ($lines * 2) - 1,
            ($lines * 2),
            ($lines * 2) + 1,
        ]);

        foreach ($s = self::getMethodSourceLines($class, $method) as $l) {
            $this->assertContains($l, $e->getContextFileSnippet(count($s)));
        }
    }

    public function testFileDiff(): void
    {
        $e = $this->getExceptionUsingNewKeyword('A %s.', ['message']);
        $m = self::getMethodMinSourceLinesFromStartOrUntilEnd($this, 'getExceptionUsingNewKeyword');

        for ($i = 1; $i < $m / 2; ++$i) {
            $this->assertCount(($i * 2) + 1, $e->getContextFileSnippet($i));
        }

        foreach (self::getMethodSourceLines($this, 'getExceptionUsingNewKeyword') as $l) {
            $this->assertContains($l, $e->getContextFileSnippet(10));
        }

        $e = $this->getExceptionUsingStaticFunc('A %s.', ['message']);

        for ($i = 1; $i < 6; ++$i) {
            $this->assertCount(($i * 2) + 1, $e->getContextFileSnippet($i));
        }

        foreach (self::getMethodSourceLines($this, 'getExceptionUsingStaticFunc') as $l) {
            $this->assertContains($l, $e->getContextFileSnippet(10));
        }
    }

    public function testDefaults(): void
    {
        $e = $this->getExceptionUsingNewKeyword('A %s.', ['message']);
        $this->assertSame('A message.', $e->getMessage());

        $e = $this->getExceptionUsingNewKeyword();
        $this->assertNotNull($e->getMessage());
        $this->assertNotNull($e->getCode());
    }

    public function testToString(): void
    {
        $e = $this->getExceptionUsingNewKeyword();
        $a = [
            'index-01' => 'value-01',
            'index-02' => 'value-02',
        ];

        $this->assertRegExp('{Exception: A test exception \(in "[^"]+" at "[^"]+"}', $e->__toString());

        foreach ($a as $i => $v) {
            $e->setAttribute($i, $v);
        }
        $this->assertRegExp('{Attributes: \[index-01\]=value-01, \[index-02\]=value-02}', $e->__toString());
    }

    public function testStringifyComplex(): void
    {
        $a = new class() {
            private $inner;

            public function setInner($inner): void
            {
                $this->inner = $inner;
            }
        };

        $b = new class() {
            private $inner;

            public function setInner($inner): void
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

        $e = $this->getExceptionUsingNewKeyword('Complex stringify replacements like "%s" and "%s" and "%s"', [$a, [$a, $b], $c]);
        $this->assertNotNull($e->getMessage());
        $this->assertStringMatchesFormat('%sabcdef0123%s', $e->getMessage());
    }

    public function testType(): void
    {
        $this->assertRegExp('{^Exception$}', $this->getExceptionUsingNewKeyword()->getType());
        $this->assertRegExp('{^SR\\\}', $this->getExceptionUsingNewKeyword()->getType(true));
    }

    public function testTypeQualified(): void
    {
        $e = $this->getExceptionUsingNewKeyword();

        $this->assertNotRegExp('{^Exception$}', $e->getType(true));
        $this->assertRegExp('{Exception$}', $e->getType(true));
    }

    /**
     * @return array[]
     */
    public static function provideInterpolationData(): array
    {
        return [
            [
                'A %s with number: "%d"',
                ['string', 10],
                'A string with number: "10"',
            ],
            [
                'Second string with number: "%d"',
                [100],
                'Second string with number: "100"',
            ],
            [
                'Second string with number "%04d" and undefined string "%s"',
                [100],
                'Second string with number "0100" and undefined string "[%s (expected a "string" to be presented as a "string")]"',
            ],
            [
                'Second string with undefined number "%04d" and undefined string "%s"',
                [],
                'Second string with undefined number "[%d (expected an "integer" to be presented as a "decimal number" (signed))]" and undefined string "[%s (expected a "string" to be presented as a "string")]"',
            ],
            [
                'Second string with number "%d" and string "%s"',
                [1, 'bar', 'foo-bar'],
                'Second string with number "1" and string "bar"',
            ],
            [
                'Argument swapping with non-provided arguments: %2$s %1$d %2$s %1$d %2$s %1$d %3$\'.09d',
                [100],
                'Argument swapping with non-provided arguments: [%s (expected a "string" to be presented as a "string")] 100 [%s (expected a "string" to be presented as a "string")] 100 [%s (expected a "string" to be presented as a "string")] 100 [%d (expected an "integer" to be presented as a "decimal number" (signed))]',
            ],

            [
                'Random specification types: "%e", "%\'.9d", "%\'.09d", "%3$\'.09d", "%\'#10s", "%4$s", "%2$04d", "%+d", "%-10s", "%10.10s", "%01.2f", "%\':4d", "%-\':4d", "%-04d", "%11$-\'14d", "%11$-\'04d", "%8$.15F", "%b", "%\'.7.4s"',
                ['1.2E+2', 900, 1234, 'string', -9, 'abc', '123', 1.234, 22, -22, 833, 9999, 'xyz'],
                'Random specification types: "1.200000e+2", "......900", "000001234", "000001234", "####string", "string", "0900", "-9", "abc       ", "       123", "1.23", "::22", "-22:", "833 ", "8331", "833 ", "1.234000000000000", "10011100001111", "....xyz"',
            ],

            [
                'Random specification types (with missing arguments): "%e", "%\'.9d", "%\'.09d", "%3$\'.09d", "%\'#10s", "%4$s", "%2$04d", "%+d", "%-10s", "%10.10s", "%01.2f", "%\':4d", "%-\':4d", "%-04d", "%11$-\'14d", "%11$-\'04d", "%8$.15F", "%b", "%\'.7.4s"',
                ['1.2E+2', 900, 1234, 'string', -9, 'abc', '123', 1.234, 22, -22],
                'Random specification types (with missing arguments): "1.200000e+2", "......900", "000001234", "000001234", "####string", "string", "0900", "-9", "abc       ", "       123", "1.23", "::22", "-22:", "[%d (expected an "integer" to be presented as a "decimal number" (signed))]", "[%d (expected an "integer" to be presented as a "decimal number" (signed))]", "[%d (expected an "integer" to be presented as a "decimal number" (signed))]", "1.234000000000000", "[%b (expected an "integer" to be presented as a "binary number")]", "[%s (expected a "string" to be presented as a "string")]"',
            ],
        ];
    }

    /**
     * @dataProvider provideInterpolationData
     *
     * @param string $format
     * @param array  $replacements
     * @param string $expected
     */
    public function testCompileMessage(string $format, array $replacements, string $expected): void
    {
        $this->assertSame($expected, $this->getExceptionUsingNewKeyword($format, $replacements)->getMessage());
    }

    public function testCreate(): void
    {
        $e = Exception::create();

        $this->assertRegExp('{ExceptionTest.php$}', $e->getFile());
    }

    public function testToArray(): void
    {
        $e = new Exception();
        $a = $e->__toArray();

        foreach (['type', 'message', 'code', 'class', 'method', 'file-name', 'file-line', 'file-diff', 'attributes', 'traceable'] as $key) {
            $this->assertArrayHasKey($key, $a);
        }

        $this->assertSame($e->getTrace(), $a['traceable']());
    }

    public function testPrevious(): void
    {
        $e = new Exception('', $p = new Exception());

        $this->assertSame($p, $e->getPrevious());
    }

    public function testAttributes(): void
    {
        $e = new Exception('', $p = new Exception());
        $a = [
            'index-01' => 'value-01',
            'index-02' => 'value-02',
        ];

        foreach ($a as $i => $v) {
            $this->assertFalse($e->hasAttribute($i));
            $e->setAttribute($i, $v);
            $this->assertSame($v, $e->getAttribute($i));
            $this->assertTrue($e->hasAttribute($i));
        }
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Exception
     */
    public function getExceptionMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this
            ->getMockBuilder(Exception::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testToStringExceptionNotThrownOnBadContext(): void
    {
        $e = $this->getExceptionMock();
        $e->__construct('Mock exception');

        try {
            $r = new \ReflectionObject($e);
            $p = $r->getProperty('file');
            $p->setAccessible(true);
            $p->setValue($e, realpath(__DIR__.'/Fixtures/NoClass.php'));
        } catch (\ReflectionException $e) {
            $this->fail(sprintf(
                'Failed to create reflection object for "%s" or to resolve property "file" of same.', get_class($e)
            ));
        }

        $this->assertNull($e->getContextClass());
        $this->assertNull($e->getContextClassName());
        $this->assertNull($e->getContextMethod());
        $this->assertNull($e->getContextMethodName());
        $this->assertCount(0, $e->getContextFileSnippet());
    }

    /**
     * @param string $message
     * @param array  $replacements
     *
     * @return ExceptionInterface|Exception
     */
    private function getExceptionUsingNewKeyword($message = 'A test exception', array $replacements = []): ExceptionInterface
    {
        return new Exception($message, ...$replacements);
    }

    /**
     * @param string $message
     * @param array  $replacements
     *
     * @return ExceptionInterface|Exception
     */
    private function getExceptionUsingStaticFunc($message = 'A test exception', array $replacements = []): ExceptionInterface
    {
        return Exception::create($message, ...$replacements);
    }

    /**
     * @param object|string $object
     * @param string        $method
     *
     * @return array
     */
    private static function getMethodSourceLines($object, string $method): array
    {
        $m = ClassQuery::getNonAccessibleMethodReflection($method, $object);

        return array_map(function (string $line): string {
            return trim($line, "\n");
        }, array_slice(file($m->getFileName()), $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));
    }

    /**
     * @param object|string $object
     * @param string        $method
     *
     * @return int
     */
    private static function getMethodMinSourceLinesFromStartOrUntilEnd($object, string $method): int
    {
        $m = ClassQuery::getNonAccessibleMethodReflection($method, $object);

        return min(
            $position = floor(($m->getStartLine() + $m->getEndLine()) / 2),
            count(file($m->getFileName())) - $position
        );
    }
}
