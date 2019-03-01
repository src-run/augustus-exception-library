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

interface ExceptionContextInterface extends \Throwable
{
    /**
     * Returns a file context class instance.
     *
     * @return FileContextInterface
     */
    public function getContext(): FileContextInterface;

    /**
     * Returns the reflection class of the thrown exception's context.
     *
     * @return \ReflectionClass|null
     */
    public function getContextClass(): ?\ReflectionClass;

    /**
     * Returns the class name of the thrown exception's context.
     *
     * @param bool $qualified
     *
     * @return string|null
     */
    public function getContextClassName(bool $qualified = true): ?string;

    /**
     * Returns the reflection method of the thrown exception's context.
     *
     * @return \ReflectionMethod|null
     */
    public function getContextMethod(): ?\ReflectionMethod;

    /**
     * Returns the method name of the thrown exception's context.
     *
     * @param bool $qualified
     *
     * @return string|null
     */
    public function getContextMethodName(bool $qualified = false): ?string;

    /**
     * Returns file lines for the line context.
     *
     * @param int $lines
     *
     * @return array|\string[]
     */
    public function getContextFileSnippet(int $lines = 3): array;
}
