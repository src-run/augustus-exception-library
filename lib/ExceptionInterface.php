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
interface ExceptionInterface
{
    /**
     * Generic exception message. Should be avoided.
     *
     * @var string
     */
    const MSG_GENERIC = 'An undefined exception was thrown. %s';

    /**
     * Exception code for an unknown/undefined state.
     *
     * @var int
     */
    const CODE_UNKNOWN = -10;

    /**
     * Generic exception code for...absolutely generic, unspecified exceptions.
     *
     * @var int
     */
    const CODE_GENERIC = -5;

    /**
     * Generic exception code for exceptions thrown from within Wonka library.
     *
     * @var int
     */
    const CODE_GENERIC_FROM_LIBRARY = 1000;

    /**
     * Generic exception code for exceptions thrown from within Wonka bundle.
     *
     * @var int
     */
    const CODE_GENERIC_FROM_BUNDLE = 2000;

    /**
     * Exception code for generic invalid arguments exception.
     *
     * @var int
     */
    const CODE_INVALID_ARGS = 50;

    /**
     * Exception code for an invalid style being passed by user.
     *
     * @var int
     */
    const CODE_INVALID_STYLE = 51;

    /**
     * Exception code for generic missing arguments.
     *
     * @var int
     */
    const CODE_MISSING_ARGS = 100;

    /**
     * Exception code for a missing entity.
     *
     * @var int
     */
    const CODE_MISSING_ENTITY = 101;

    /**
     * Exception code for an unknown/missing service.
     *
     * @var int
     */
    const CODE_MISSING_SERVICE = 201;

    /**
     * Exception code for an inconsistent fixture data error.
     *
     * @var int
     */
    const CODE_FIXTURE_DATA_INCONSISTENT = 735;

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
     * @param \Exception $exception
     *
     * @return $this
     */
    public function setPrevious(\Exception $exception);

    /**
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes = []);

    /**
     * @param mixed       $value
     * @param null|string $index
     *
     * @return $this
     */
    public function addAttribute($value, $index = null);

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @param string $index
     *
     * @return null|mixed
     */
    public function getAttribute($index);

    /**
     * @param string $index
     *
     * @return bool
     */
    public function hasAttribute($index);

    /**
     * @return array
     */
    public function getTraceLimited();

    /**
     * @param false|bool $fqcn
     *
     * @return string
     */
    public function getType($fqcn = false);
}

/* EOF */
