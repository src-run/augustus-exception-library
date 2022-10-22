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

interface ExceptionAttributesInterface extends \Throwable
{
    /**
     * Returns the attributes array.
     */
    public function getAttributes(): array;

    /**
     * Sets an attribute property using the index and value provided.
     *
     * @param string $index Index string
     * @param mixed  $value Value to set
     */
    public function setAttribute(string $index, mixed $value): ExceptionInterface;

    /**
     * Returns true if an attribute with the specified index exists.
     *
     * @param string $index The attribute index to search for
     */
    public function hasAttribute(string $index): bool;

    /**
     * Returns the value of an attribute with the specified index, or null if such an attribute does not exist.
     *
     * @param string $index The attribute index to search for
     *
     * @return mixed|null
     */
    public function getAttribute(string $index): mixed;
}
