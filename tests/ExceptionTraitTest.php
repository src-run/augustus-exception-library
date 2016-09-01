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
     * @return ExceptionTrait|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getExceptionTrait()
    {
        return $this->getMockBuilder('SR\Exception\ExceptionTrait')
            ->disableOriginalConstructor()
            ->getMockForTrait();
    }

    public function testToArray()
    {
        $array = $this->getExceptionTrait()->__toArray();

        foreach (['type', 'message', 'fileName', 'fileLine', 'code', 'attributes', 'traceable'] as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }

    public function testHasAndSetAndGetAttributes()
    {
        $exception = $this->getExceptionTrait();
        $attributes = [
            'index-01' => 'value-01',
            'index-02' => 'value-02',
        ];

        $this->assertFalse($exception->hasAttributes());
        $exception->attributes($attributes);

        $this->assertTrue($exception->hasAttributes());
        $this->assertSame($attributes, $exception->getAttributes());
    }

    public function testHasAndSetAndGetAttribute()
    {
        $exception = $this->getExceptionTrait();
        $attributes = [
            'index-01' => 'value-01',
            'index-02' => 'value-02',
        ];

        foreach ($attributes as $i => $v) {
            $this->assertFalse($exception->hasAttribute($i));
            $exception->attribute($i, $v);
            $this->assertSame($v, $exception->getAttribute($i));
            $this->assertTrue($exception->hasAttribute($i));
        }
    }
}

/* EOF */
