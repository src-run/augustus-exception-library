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

use SR\Utilities\Context\FileContextInterface;

interface ExceptionInterface extends \Throwable
{
    /**
     * Return string representation of exception.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Return array representation of exception.
     *
     * @return mixed[]
     */
    public function __toArray(): array;

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     *
     * @return ExceptionInterface
     */
    public static function create(string $message = null, ...$parameters): self;

    /**
     * Returns the exception type (class name) as either a fully-qualified class name or as just the class base name.
     *
     * @param bool $qualified
     *
     * @return string
     */
    public function getType(bool $qualified = false): string;

    /**
     * Returns a file context class instance.
     *
     * @return FileContextInterface
     */
    public function getContext(): FileContextInterface;

    /**
     * Returns the class name of the thrown exception's context.
     *
     * @return string
     */
    public function getContextClass(): string;

    /**
     * Returns the method name of the thrown exception's context.
     *
     * @return string
     */
    public function getContextMethod(): string;

    /**
     * Returns file lines for the line context.
     *
     * @param int $lines
     *
     * @return array|\string[]
     */
    public function getContextFileSnippet(int $lines = 3): array;

    /**
     * Returns the attributes array.
     *
     * @return array
     */
    public function getAttributes(): array;

    /**
     * Sets an attribute property using the index and value provided.
     *
     * @param string $index Index string
     * @param mixed  $value Value to set
     *
     * @return ExceptionInterface
     */
    public function setAttribute(string $index, $value): self;

    /**
     * Returns true if an attribute with the specified index exists.
     *
     * @param string $index The attribute index to search for
     *
     * @return bool
     */
    public function hasAttribute(string $index): bool;

    /**
     * Returns the value of an attribute with the specified index, or null if such an attribute does not exist.
     *
     * @param string $index The attribute index to search for
     *
     * @return null|mixed
     */
    public function getAttribute(string $index);
}

/* EOF */
