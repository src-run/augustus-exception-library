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
    private array $attributes = [];

    /**
     * Returns the attributes array.
     */
    final public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Sets an attribute property using the index and value provided.
     */
    final public function setAttribute(string $index, $value): ExceptionInterface|self
    {
        $this->attributes[$index] = $value;

        return $this;
    }

    /**
     * Returns true if an attribute with the specified index exists.
     */
    final public function hasAttribute(string $index): bool
    {
        return isset($this->attributes[$index]);
    }

    /**
     * Returns the value of an attribute with the specified index, or null if such an attribute does not exist.
     */
    final public function getAttribute(string $index): mixed
    {
        return $this->hasAttribute($index) ? $this->attributes[$index] : null;
    }
}
