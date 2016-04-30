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

use SR\Polyfill\Php70\Assert;
use SR\Utility\ClassInspect;

/**
 * Class ExceptionTrait.
 */
trait ExceptionTrait
{
    /**
     * @var mixed[]
     */
    protected $attributes;

    /**
     * @var string|null
     */
    protected $messageOriginal;

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     */
    final public function __construct($message = null, ...$parameters)
    {
        $this->setAttributes([]);

        $replacements = $this->filterReplacementParameters($parameters);
        $throwable = $this->filterFirstThrowableParameter($parameters);

        parent::__construct(
            $this->compileMessage($message, ...$replacements),
            $this->compileCode(null),
            $this->compilePrevious($throwable)
        );
    }

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     *
     * @return static
     */
    final public static function create($message = null, ...$parameters)
    {
        return new static($message, ...$parameters);
    }

    /**
     * @return string
     */
    final public function __toString()
    {
        $stringSet = [
            'type' => $this->getType(true),
            'text' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];

        return print_r((array) $stringSet, true);
    }

    /**
     * @return mixed[]
     */
    final public function __debugInfo()
    {
        return [
            'type' => $this->getType(true),
            'text' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'more' => $this->getAttributes(),
            'back' => $this->getTraceLimited(),
        ];
    }

    /**
     * @return string
     */
    abstract public function getMessage();

    /**
     * @return int
     */
    abstract public function getCode();

    /**
     * @return string
     */
    abstract public function getFile();

    /**
     * @return int
     */
    abstract public function getLine();

    /**
     * @return null|\Throwable|\Exception|\Error
     */
    abstract public function getPrevious();

    /**
     * @return mixed[]
     */
    abstract public function getTrace();

    /**
     * @return string
     */
    public function getDefaultMessage()
    {
        return ExceptionInterface::MSG_GENERIC;
    }

    /**
     * @return int
     */
    public function getDefaultCode()
    {
        return ExceptionInterface::CODE_GENERIC;
    }

    /**
     * @param mixed ...$parameters
     *
     * @return $this
     */
    final public function with(...$parameters)
    {
        $replacements = $this->filterReplacementParameters($parameters);
        $throwable = $this->filterFirstThrowableParameter($parameters);

        if ($throwable !== null) {
            $this->setPrevious($throwable);
        }

        if (count($replacements) > 0) {
            $this->setMessage($this->messageOriginal, ...$replacements);
        }

        return $this;
    }

    /**
     * @param string $message
     * @param mixed  ...$replacements
     *
     * @return $this
     */
    final public function setMessage($message, ...$replacements)
    {
        $this->message = $this->compileMessage($message, ...$replacements);

        return $this;
    }

    /**
     * @param int $code
     *
     * @return $this
     */
    final public function setCode($code)
    {
        $this->code = $this->compileCode($code);

        return $this;
    }

    /**
     * @param string|\SplFileInfo $file
     *
     * @return $this
     */
    final public function setFile($file)
    {
        $this->file = $this->compileFile($file);

        return $this;
    }

    /**
     * @param int $line
     *
     * @return $this
     */
    final public function setLine($line)
    {
        $this->line = $this->compileLine($line);

        return $this;
    }

    /**
     * @param null|\Exception|\Throwable|\Error $throwable
     *
     * @return $this
     */
    final public function setPrevious($throwable)
    {
        $this->__construct($this->messageOriginal ?: $this->getMessage(), $throwable);

        return $this;
    }

    /**
     * @param mixed[] $attributes
     *
     * @return $this
     */
    final public function setAttributes(array $attributes = [])
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return mixed[]
     */
    final public function getAttributes()
    {
        return (array) $this->attributes;
    }

    /**
     * @param mixed       $attribute
     * @param null|string $key
     *
     * @return $this
     */
    final public function addAttribute($attribute, $key = null)
    {
        if ($key === null || empty($key)) {
            $this->attributes[] = $attribute;
        } else {
            $this->attributes[$key] = $attribute;
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return null|mixed
     */
    final public function getAttribute($key)
    {
        if (!$this->hasAttribute($key)) {
            return null;
        }

        return $this->attributes[$key];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    final public function hasAttribute($key)
    {
        return (bool) isset($this->attributes[$key]);
    }

    /**
     * @return mixed[]
     */
    final public function getTraceLimited()
    {
        $trace = (array) $this->getTrace();

        array_walk($trace, function (&$t) {
            array_walk($t['args'], function (&$a) {
                $a = is_object($a) ? get_class($a) : $a;
            });
        });

        return (array) $trace;
    }

    /**
     * @param bool $qualified
     *
     * @return string
     */
    final public function getType($qualified = false)
    {
        $class = get_called_class();

        return $qualified ?
            ClassInspect::getNameQualified($class) :
            ClassInspect::getNameShort($class);
    }

    /**
     * @param mixed[] $parameters
     *
     * @return mixed[]
     */
    final protected function filterReplacementParameters(array $parameters = [])
    {
        $replacements = array_filter($parameters, function ($param) {
            return !Assert::throwableEquitable($param);
        });

        return $replacements;
    }

    /**
     * @param mixed[] $parameters
     *
     * @return \Throwable[]|\Exception[]|\Error[]
     */
    final protected function filterThrowableParameters(array $parameters = [])
    {
        $throwables = array_filter($parameters, function ($param) {
            return Assert::throwableEquitable($param);
        });

        return $throwables;
    }

    /**
     * @param mixed[] $parameters
     *
     * @return \Throwable|\Exception|\Error
     */
    final protected function filterFirstThrowableParameter(array $parameters = [])
    {
        $throwables = $this->filterThrowableParameters($parameters);

        if (count($throwables) !== 0) {
            return array_shift($throwables);
        }

        return null;
    }

    /**
     * @param null|string $message
     * @param mixed       ...$replacements
     *
     * @return string
     */
    final protected function compileMessage($message = null, ...$replacements)
    {
        $this->messageOriginal = $message;

        $message = $message ?: $this->getDefaultMessage();

        if (count($replacements) > 0) {
            $message = @sprintf($message, ...$replacements) ?: $message;
        }

        return preg_replace_callback('{%[0-9ds][0-9]?(?:\$[0-9]?[0-9]?[a-z]?)?}i', function () {
            return '<null>';
        }, $message);
    }

    /**
     * @param null|int $code
     *
     * @return int
     */
    final protected function compileCode($code = null)
    {
        return $code !== null ? $code : $this->getDefaultCode();
    }

    /**
     * @param null|string|\SplFileInfo $file
     *
     * @return string|null
     */
    final protected function compileFile($file = null)
    {
        if ($file instanceof \SplFileInfo) {
            return $file->getPathname();
        }

        return $file ?: null;
    }

    /**
     * @param null|int $line
     *
     * @return int|null
     */
    final protected function compileLine($line = null)
    {
        return is_int($line) ? $line : null;
    }

    /**
     * @param null|\Throwable|\Exception|\Error $throwable
     *
     * @return null|\Throwable|\Exception|\Error
     */
    final protected function compilePrevious($throwable = null)
    {
        return Assert::throwableEquitable($throwable) ? $throwable : null;
    }
}

/* EOF */
