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

use SR\Exception\Exception;

/**
 * Class ExceptionTest.
 */
class ExceptionTest extends \PHPUnit_Framework_TestCase
{
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

        $this->assertRegExp('{Exception \'Exception\' with message \'A test exception\' in}', $exception->__toString());

        $exception->setAttributes($attributes);
        $this->assertRegExp('{with attributes \'\[index-01\]=value-01, \[index-02\]=value-02\'}', $exception->__toString());
    }

    public function testType()
    {
        $this->assertRegExp('{^Exception$}', $this->getException()->getType());
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

        $exception->setMessage('Second string with number: "%d"', 100);
        $this->assertSame('Second string with number: "100"', $exception->getMessage());

        $exception->setMessage('Second string with number "%04d" and undefined string "%s"', 100);
        $this->assertSame('Second string with number "0100" and undefined string "<string:undefined>"', $exception->getMessage());

        $exception->setMessage('Second string with undefined number "%04d" and undefined string "%s"');
        $this->assertSame('Second string with undefined number "<integer:undefined>" and undefined string "<string:undefined>"', $exception->getMessage());
    }

    public function testCreate()
    {
        $exception = Exception::create();

        $this->assertRegExp('{ExceptionTest.php$}', $exception->getFile());
    }
}

/* EOF */
