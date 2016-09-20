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

use SR\Exception\AbstractException;

/**
 * Class ExceptionTraitTest.
 */
class AbstractExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return AbstractException|\PHPUnit_Framework_MockObject_MockObject
     */
    public function createAbstractExceptionMock()
    {
        return $this->getMockBuilder(AbstractException::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testHasAndSetAndGetAttributes()
    {
        $exception = $this->createAbstractExceptionMock();
        $attributes = [
            'index-01' => 'value-01',
            'index-02' => 'value-02',
        ];

        $this->assertFalse($exception->hasAttributes());
        $exception->setAttributes($attributes);

        $this->assertTrue($exception->hasAttributes());
        $this->assertSame($attributes, $exception->getAttributes());
    }

    public function testHasAndSetAndGetAttribute()
    {
        $exception = $this->createAbstractExceptionMock();
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
