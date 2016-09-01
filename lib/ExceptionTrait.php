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
    protected $attributes = [];

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     */
    final public function __construct($message = null, ...$parameters)
    {
        parent::__construct($this->compileMessage($message, $parameters), 0, $this->filterThrowables($parameters));
    }

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     *
     * @return static
     */
    final public static function create($message = null, ...$parameters)
    {
        $instance = new static($message, ...$parameters);
        $reflects = new \ReflectionObject($instance);

        $method = $reflects->getMethod('reassignTargetOnStaticConstruct');
        $method->setAccessible(true);
        $method->invoke($instance);

        return $instance;
    }

    /**
     * @param bool $qualified
     *
     * @return string
     */
    final public function getType($qualified = false)
    {
        return $qualified ? static::class : ClassInspect::getNameShort(static::class);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param mixed  ...$replacements
     *
     * @return $this
     */
    final public function message($message, ...$replacements)
    {
        $this->message = $this->compileMessage($message, $replacements);

        return $this;
    }

    /**
     * @param mixed[] $attributes
     *
     * @return $this
     */
    final public function attributes(array $attributes = [])
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return mixed[]
     */
    final public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return bool
     */
    final public function hasAttributes()
    {
        return count($this->attributes) !== 0;
    }

    /**
     * @param string $index
     * @param mixed  $value
     *
     * @return $this
     */
    final public function attribute($index, $value)
    {
        $this->attributes[$index] = $value;

        return $this;
    }

    /**
     * @param string $index
     *
     * @return null|mixed
     */
    final public function getAttribute($index)
    {
        return $this->hasAttribute($index) ? $this->attributes[(string) $index] : null;
    }

    /**
     * @param string $index
     *
     * @return bool
     */
    final public function hasAttribute($index)
    {
        return array_key_exists((string) $index, $this->attributes) && !empty($this->attributes[(string) $index]);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    final public function __toString()
    {
        $string = vsprintf('Exception \'%s\' with message \'%s\' in %s:%d', [
            $this->getType(false),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
        ]);

        if (!$this->hasAttributes()) {
            return $string;
        }

        $attributes = $this->getAttributes();
        array_walk($attributes, function (&$value, $index) {
            $value = sprintf('[%s]=%s', $index, $this->toScalarRepresentation($value));
        });

        return sprintf('%s with attributes \'%s\'', $string, implode(', ', $attributes));
    }

    /**
     * @return mixed[]
     */
    final public function __toArray()
    {
        return [
            'type' => $this->getType(true),
            'message' => $this->getMessage(),
            'fileName' => $this->getFile(),
            'fileLine' => $this->getLine(),
            'code' => $this->getCode(),
            'attributes' => $this->getAttributes(),
            'traceable' => function () {
                return $this->getTrace();
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getMessage();

    /**
     * {@inheritdoc}
     */
    abstract public function getCode();

    /**
     * {@inheritdoc}
     */
    abstract public function getFile();

    /**
     * {@inheritdoc}
     */
    abstract public function getLine();

    /**
     * {@inheritdoc}
     */
    abstract public function getPrevious();

    /**
     * {@inheritdoc}
     */
    abstract public function getTrace();

    /**
     * @param null|string $message
     * @param mixed[]     $replacements
     *
     * @return string
     */
    final private function compileMessage($message, array $replacements)
    {
        $message = $message ?: ExceptionInterface::DEFAULT_MESSAGE;
        $replace = $this->filterReplacements($replacements);
        $cleaned = $this->cleanupMessage($message, count($replace));

        return !($compiled = @vsprintf($cleaned, $replace)) ? $this->cleanupMessage($message, 0) : $compiled;
    }

    /**
     * @param mixed[] $parameters
     *
     * @return mixed[]
     */
    final private function filterReplacements(array $parameters)
    {
        $replacements = array_filter($parameters, function ($p) {
            return !ClassInspect::isThrowableEquitable($p);
        });

        return array_map(function ($replacement) {
            return $this->toScalarRepresentation($replacement);
        }, $replacements);
    }

    /**
     * @param mixed[] $parameters
     *
     * @return \Throwable|null
     */
    final protected function filterThrowables(array $parameters)
    {
        $throwables = array_filter($parameters, function ($p) {
            return ClassInspect::isThrowableEquitable($p);
        });

        return count($throwables) === 0 ? null : array_shift($throwables);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    final private function toScalarRepresentation($value)
    {
        return is_scalar($value) ? $value : @var_export($value, true);
    }

    /**
     * @param string $message
     * @param int    $count
     *
     * @return string
     */
    final private function cleanupMessage($message, $count)
    {
        $iteration = 0;

        return preg_replace_callback('{%([0-9-]+)?([sducoxXbgGeEfF])([0-9]?(?:\$[0-9]?[0-9]?[a-zA-Z]?)?)}', function ($matches) use ($count, &$iteration) {
            return ++$iteration > $count ? '<'.$this->placeholderType($matches[2]).':undefined>' : $matches[0];
        }, $message);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    final private function placeholderType($type)
    {
        $str = 'unknown-type';
        $map = [
            'string' => ['s'],
            'integer' => ['d', 'u', 'c', 'o', 'x', 'X', 'b'],
            'double' => ['g', 'G', 'e', 'E', 'f', 'F'],
        ];

        foreach ($map as $name => $values) {
            if (in_array($type, $values)) {
                $str = $name;
            }
        }

        return  $str;
    }

    final private function reassignTargetOnStaticConstruct()
    {
        $stack = array_slice($this->getTrace(), 1);

        if (isset($stack[0]['class']) && isset($stack[0]['function'])) {
            $object = new \ReflectionClass($stack[0]['class']);
            $method = $object->getMethod($stack[0]['function']);
            $self = new \ReflectionObject($this);

            $file = $self->getProperty('file');
            $file->setAccessible(true);
            $file->setValue($this, $object->getFileName());

            $line = $self->getProperty('line');
            $line->setAccessible(true);
            $line->setValue($this, $method->getStartLine());
        }
    }
}

/* EOF */
