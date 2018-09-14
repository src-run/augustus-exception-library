<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception;

trait ExceptionAttributesTrait
{
    /**
     * @var mixed[]
     */
    private $attributes = [];

    /**
     * Returns the attributes array.
     *
     * @return array
     */
    final public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Sets an attribute property using the index and value provided.
     *
     * @param string $index Index string
     * @param mixed  $value Value to set
     *
     * @return ExceptionInterface|ExceptionTrait|ExceptionAttributesTrait
     */
    final public function setAttribute(string $index, $value): ExceptionInterface
    {
        $this->attributes[$index] = $value;

        return $this;
    }

    /**
     * Returns true if an attribute with the specified index exists.
     *
     * @param string $index The attribute index to search for
     *
     * @return bool
     */
    final public function hasAttribute(string $index): bool
    {
        return isset($this->attributes[$index]);
    }

    /**
     * Returns the value of an attribute with the specified index, or null if such an attribute does not exist.
     *
     * @param string $index The attribute index to search for
     *
     * @return null|mixed
     */
    final public function getAttribute(string $index)
    {
        return $this->hasAttribute($index) ? $this->attributes[$index] : null;
    }
}
