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
     * @param string|null $message
     * @param mixed,...   $parameters
     */
    final public function __construct($message = null, ...$parameters)
    {
        $this->setAttributes([]);

        list($previous, $replacements) = $this->parseParameters($parameters);

        parent::__construct(
            $this->compileMessage($message, ...$replacements),
            $this->compileCode(null),
            $this->compilePrevious($previous)
        );
    }

    /**
     * @param string|null $message
     * @param mixed,...   $parameters
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

        return (string) print_r((array) $stringSet, true);
    }

    /**
     * @return array
     */
    final public function __debugInfo()
    {
        return (array) [
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
     * @return \Exception|null
     */
    abstract public function getPrevious();

    /**
     * @return array
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
     * @param mixed,... ...$parameters
     *
     * @return $this
     */
    final public function with(...$parameters)
    {
        list($previous, $replacements) = $this->parseParameters($parameters);

        if ($previous instanceof \Exception) {
            $this->setPrevious($previous);
        }

        if (count($replacements) > 0) {
            $this->setMessage($this->messageOriginal, ...$replacements);
        }

        return $this;
    }

    /**
     * @param string    $message
     * @param mixed,... $replacements
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
     * @param \Exception $exception
     *
     * @return $this
     */
    final public function setPrevious(\Exception $exception)
    {
        $this->__construct($this->messageOriginal ?: $this->getMessage(), $exception);

        return $this;
    }

    /**
     * @param array $attributes
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
     * @param mixed      $value
     * @param null|mixed $index
     *
     * @return $this
     */
    final public function addAttribute($value, $index = null)
    {
        if ($index === null || empty($index)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$index] = $value;
        }

        return $this;
    }

    /**
     * @param string $index
     *
     * @return null|mixed
     */
    final public function getAttribute($index)
    {
        if (!$this->hasAttribute($index)) {
            return null;
        }

        return $this->attributes[$index];
    }

    /**
     * @param string $index
     *
     * @return bool
     */
    final public function hasAttribute($index)
    {
        return (bool) isset($this->attributes[$index]);
    }

    /**
     * @return array
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
     * @param false|bool $fqcn
     *
     * @return string
     */
    final public function getType($fqcn = false)
    {
        return $fqcn ? ClassInspect::getNameQualified(get_called_class()) :
            ClassInspect::getNameShort(get_called_class());
    }

    /**
     * @internal
     *
     * @param ExceptionInterface[]|mixed[] $parameters
     *
     * @return ExceptionInterface[]|mixed[]
     */
    final protected function parseParameters(array $parameters = [])
    {
        $throwable = null;

        $replaces = array_filter($parameters, function ($value) use (&$throwable) {
            if ($value instanceof \Throwable || $value instanceof \Exception) {
                $throwable = $value;

                return false;
            }

            return true;
        });

        return [$throwable, $replaces];
    }

    /**
     * @internal
     *
     * @param null|string $message
     * @param mixed,...   $replacements
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
     * @internal
     *
     * @param int|null $code
     *
     * @return int
     */
    final protected function compileCode($code = null)
    {
        return $code !== null ? $code : $this->getDefaultCode();
    }

    /**
     * @internal
     *
     * @param string|\SplFileInfo $file
     *
     * @return string|null
     */
    final protected function compileFile($file)
    {
        if ($file instanceof \SplFileInfo) {
            return $file->getPathname();
        }

        return $file ? $file : null;
    }

    /**
     * @internal
     *
     * @param int $line
     *
     * @return int|null
     */
    final protected function compileLine($line)
    {
        return is_int($line) ? $line : null;
    }

    /**
     * @internal
     *
     * @param null|\Exception|\Throwable $e
     *
     * @return null|\Exception|\Throwable
     */
    final protected function compilePrevious($e = null)
    {
        return ($e instanceof \Throwable || $e instanceof \Exception) ? $e : null;
    }
}

/* EOF */
