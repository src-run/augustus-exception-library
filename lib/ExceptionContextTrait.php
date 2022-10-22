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

use SR\Utilities\Context\FileContext;
use SR\Utilities\Context\FileContextInterface;

trait ExceptionContextTrait
{
    private ?FileContextInterface $context = null;

    /**
     * Returns a file context class instance.
     */
    final public function getContext(): FileContextInterface|self
    {
        if (!$this->context) {
            $this->context = new FileContext($this->getFile(), $this->getLine());
        }

        return $this->context;
    }

    /**
     * Returns the reflection class of the thrown exception's context.
     */
    final public function getContextClass(): ?\ReflectionClass
    {
        try {
            return $this->getContext()->getClass();
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Returns the class name of the thrown exception's context.
     */
    final public function getContextClassName(bool $qualified = true): ?string
    {
        try {
            return $this->getContext()->getClassName($qualified);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Returns the reflection method of the thrown exception's context.
     */
    final public function getContextMethod(): ?\ReflectionMethod
    {
        try {
            return $this->getContext()->getMethod();
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Returns the method name of the thrown exception's context.
     */
    final public function getContextMethodName(bool $qualified = false): ?string
    {
        try {
            return $this->getContext()->getMethodName($qualified);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Returns file lines for the line context.
     *
     * @return array|string[]
     */
    final public function getContextFileSnippet(int $lines = 3): array
    {
        try {
            return $this->getContext()->getFileContext($lines);
        } catch (\RuntimeException $e) {
            return [];
        }
    }
}
