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

namespace SR\Exception;

/**
 * Interface ExceptionInterface.
 */
interface ExceptionInterface extends \Throwable
{
    /**
     * @var string
     */
    const DEFAULT_MESSAGE = 'An undefined error occured!';

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     */
    public function __construct($message = null, ...$parameters);

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     *
     * @return static
     */
    public static function create($message = null, ...$parameters);

    /**
     * @param bool $qualified
     *
     * @return string
     */
    public function getType($qualified = false);

    /**
     * @param string $message
     * @param mixed  ...$replacements
     *
     * @return $this
     */
    public function message($message, ...$replacements);

    /**
     * @param mixed[] $attributes
     *
     * @return $this
     */
    public function attributes(array $attributes = []);

    /**
     * @return mixed[]
     */
    public function getAttributes();

    /**
     * @return bool
     */
    public function hasAttributes();

    /**
     * @param string $index
     * @param mixed  $value
     *
     * @return $this
     */
    public function attribute($index, $value);

    /**
     * @param string $index
     *
     * @return mixed
     */
    public function getAttribute($index);

    /**
     * @param string $index
     *
     * @return bool
     */
    public function hasAttribute($index);

    /**
     * @return mixed[]
     */
    public function __toArray();
}

/* EOF */
