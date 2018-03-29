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
    /**
     * @var FileContextInterface
     */
    private $context;

    /**
     * Returns a file context class instance.
     *
     * @return FileContextInterface|ExceptionTrait
     */
    final public function getContext(): FileContextInterface
    {
        if (!$this->context) {
            $this->context = new FileContext($this->getFile(), $this->getLine());
        }

        return $this->context;
    }

    /**
     * Returns the class name of the thrown exception's context.
     *
     * @return null|string
     */
    final public function getContextClass(): ?string
    {
        try {
            return $this->getContext()->getClassName(true);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Returns the method name of the thrown exception's context.
     *
     * @return null|string
     */
    final public function getContextMethod(): ?string
    {
        try {
            return $this->getContext()->getMethodName(true);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Returns file lines for the line context.
     *
     * @param int $lines
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
