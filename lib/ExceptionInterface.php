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
    const MSG_GENERIC = 'Unspecified exception.';

    /**
     * @var int
     */
    const CODE_GENERIC = -1;

    /**
     * @var string
     */
    const MSG_BAD_FUNCTION_CALL = 'Invalid function call.';

    /**
     * @var int
     */
    const CODE_BAD_FUNCTION_CALL = 1001;

    /**
     * @var string
     */
    const MSG_INVALID_ARGUMENT = 'Invalid argument.';

    /**
     * @var int
     */
    const CODE_INVALID_ARGUMENT = '1002';

    /**
     * @var string
     */
    const MSG_LOGIC = 'Logic error.';

    /**
     * @var int
     */
    const CODE_LOGIC = 1003;

    /**
     * @var string
     */
    const MSG_RUNTIME = 'Runtime error.';

    /**
     * @var int
     */
    const CODE_RUNTIME = 1004;

    /**
     * @param string|null $message
     * @param mixed,...   $parameters
     *
     * @return static
     */
    public static function create($message = null, ...$parameters);

    /**
     * @return array
     */
    public function __debugInfo();

    /**
     * @return string
     */
    public function __toString();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return int
     */
    public function getCode();

    /**
     * @return string
     */
    public function getFile();

    /**
     * @return int
     */
    public function getLine();

    /**
     * @return \Exception|null
     */
    public function getPrevious();

    /**
     * @return array
     */
    public function getTrace();

    /**
     * @param mixed,... ...$parameters
     *
     * @return $this
     */
    public function with(...$parameters);

    /**
     * @param string    $message
     * @param mixed,... $replacements
     *
     * @return $this
     */
    public function setMessage($message, ...$replacements);

    /**
     * @param int $code
     *
     * @return $this
     */
    public function setCode($code);

    /**
     * @param string|\SplFileInfo $file
     *
     * @return $this
     */
    public function setFile($file);

    /**
     * @param int $line
     *
     * @return $this
     */
    public function setLine($line);

    /**
     * @param \Throwable|\Exception $thowable
     *
     * @return $this
     */
    public function setPrevious($thowable);

    /**
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes = []);

    /**
     * @param mixed       $attribute
     * @param null|string $key
     *
     * @return $this
     */
    public function addAttribute($attribute, $key = null);

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @param string $key
     *
     * @return null|mixed
     */
    public function getAttribute($key);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasAttribute($key);

    /**
     * @return array
     */
    public function getTraceLimited();

    /**
     * @param false|bool $qualified
     *
     * @return string
     */
    public function getType($qualified = false);
}

/* EOF */
