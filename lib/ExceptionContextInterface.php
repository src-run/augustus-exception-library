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
     * Returns the class name of the thrown exception's context.
     *
     * @return null|string
     */
    public function getContextClass(): ?string;

    /**
     * Returns the method name of the thrown exception's context.
     *
     * @return null|string
     */
    public function getContextMethod(): ?string;

    /**
     * Returns file lines for the line context.
     *
     * @param int $lines
     *
     * @return array|\string[]
     */
    public function getContextFileSnippet(int $lines = 3): array;
}
