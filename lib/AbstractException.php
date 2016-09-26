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

use SR\Silencer\CallSilencer;
use SR\Util\Context\FileContext;
use SR\Util\Info\ClassInfo;

/**
 * The base, abstract exception class used by all concrete implementations.
 */
abstract class AbstractException extends \Exception implements ExceptionInterface
{
    /**
     * @var mixed[]
     */
    private $attributes = [];

    /**
     * @var string
     */
    protected $message = null;

    /**
     * @var FileContext
     */
    protected $context;

    /**
     * @param null|string $message
     * @param mixed       ...$params
     */
    public function __construct(string $message = null, ...$params)
    {
        parent::__construct($this->compileMessage($message ?: $this->defaultMessage(), $params), null, $this->compileThrown($params));
    }

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     *
     * @return ExceptionInterface
     */
    final public static function create(string $message = null, ...$parameters) : ExceptionInterface
    {
        $object = new static($message, ...$parameters);
        $method = (new \ReflectionObject($object))->getMethod('doContextReassignmentOnStaticInstantiation');
        $method->setAccessible(true);
        $method->invoke($object);

        return $object;
    }

    /**
     * Return string representation of exception.
     *
     * @return string
     */
    final public function __toString() : string
    {
        list($context, $class, $method, $snippet) = $this->contextArray();

        $message = vsprintf('%s: %s (in "%s" at "%s:%d").', [
            $this->getType(false),
            $this->getMessage(),
            $method,
            $this->getFile(),
            $this->getLine(),
        ]);

        if (count($this->attributes) > 0) {
            $message .= sprintf(' Attributes: %s', $this->attributesToString());
        }

        return $message;
    }

    /**
     * Return array representation of exception.
     *
     * @return mixed[]
     */
    final public function __toArray() : array
    {
        list($context, $class, $method, $snippet) = $this->contextArray();

        return [
            'type' => $this->getType(true),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $context,
            'class' => $class,
            'method' => $method,
            'file-name' => $this->getFile(),
            'file-line' => $this->getLine(),
            'file-diff' => $snippet,
            'attributes' => $this->getAttributes(),
            'traceable' => function () {
                return $this->getTrace();
            },
        ];
    }

    /**
     * Returns the exception type (class name) as either a fully-qualified class name or as just the class base name.
     *
     * @param bool $qualified
     *
     * @return string
     */
    final public function getType(bool $qualified = false) : string
    {
        return $qualified ? static::class : ClassInfo::getNameShort(static::class);
    }

    /**
     * Returns a file context class instance.
     *
     * @return FileContext
     */
    final public function getContext() : FileContext
    {
        return $this->initContext()->context;
    }

    /**
     * Returns the class name of the thrown exception's context.
     *
     * @return string
     */
    final public function getContextClass() : string
    {
        return $this->getContext()->getClassName(true);
    }

    /**
     * Returns the method name of the thrown exception's context.
     *
     * @return string
     */
    final public function getContextMethod() : string
    {
        return $this->getContext()->getMethodName(true);
    }

    /**
     * Returns file lines for the line context.
     *
     * @param int $lines
     *
     * @return array|\string[]
     */
    final public function getContextFileSnippet(int $lines = 3) : array
    {
        return $this->getContext()->getFileContext($lines);
    }

    /**
     * Assign the exception message. All parameters following the first are treated as replacements for the first
     * parameter using {@see vsprintf}.
     *
     * @param string $message
     * @param mixed  ...$params
     *
     * @return ExceptionInterface
     */
    final public function setMessage(string $message = null, ...$params) : ExceptionInterface
    {
        $this->message = $this->compileMessage($message, $params);

        return $this;
    }

    /**
     * Sets an attribute property using the index and value provided.
     *
     * @param string $index Index string
     * @param mixed  $value Value to set
     *
     * @return ExceptionInterface
     */
    final public function setAttribute(string $index, $value) : ExceptionInterface
    {
        $this->attributes[$index] = $value;

        return $this;
    }

    /**
     * Returns the value of an attribute with the specified index, or null if such an attribute does not exist.
     *
     * @param string $index The attribute index to search for
     *
     * @return null|mixed
     */
    final public function getAttribute(string $index)
    {
        return $this->hasAttribute($index) ? $this->attributes[$index] : null;
    }

    /**
     * Returns the attributes array.
     *
     * @return array
     */
    final public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * Returns true if an attribute with the specified index exists.
     *
     * @param string $index The attribute index to search for
     *
     * @return bool
     */
    final public function hasAttribute(string $index) : bool
    {
        return isset($this->attributes[$index]) && !empty($this->attributes[$index]);
    }

    /**
     * @return string
     */
    protected function defaultMessage() : string
    {
        return 'An unspecified exception was thrown during code execution';
    }

    /**
     * @return AbstractException
     */
    final private function initContext() : AbstractException
    {
        if (!$this->context) {
            $this->context = new FileContext($this->getFile(), $this->getLine());
        }

        return $this;
    }

    /**
     * Handle "compilation" of the final previous exception by filtering the passed parameters for instances of \Throwable
     * and returning the first instance found.
     *
     * @param mixed[] $params
     *
     * @return \Throwable|null
     */
    final private function compileThrown(array $params = [])
    {
        if (empty($thrown = $this->filterThrowable($params))) {
            return null;
        }

        return array_shift($thrown);
    }

    /**
     * Handle compilation of the final message using a string value and an optional array of replacements. This internal
     * function {@see vsprintf} is used, so reference it's documentation for acceptable placeholder syntax of the
     * string. Failure of the {@see vsprintf} call (which happens when, for example, the message string contains a
     * different number of placeholder than the number of replacements provided) will not fail or return null, but
     * instead return the message string in its un-compiled form.
     *
     * @param null|string $message
     * @param mixed[]     $replace
     *
     * @return string|null
     */
    final private function compileMessage(string $message = null, array $replace = [])
    {
        $replace = $this->filterNotThrowable($replace);

        return $this->compileMessagePlaceholders($message, $replace, false) ?:
            $this->compileMessagePlaceholders($message, $replace, true);
    }

    /**
     * Try to compile message with provided string and replacement array.
     *
     * @param string  $message The message string, which may contain placeholders for vsprintf
     * @param mixed[] $replace Array of replacements for the string
     * @param bool    $removes If true extra placeholders will be removed from the string such that the number of
     *                         placeholders matches the number of replacements
     *
     * @return bool|string
     */
    final private function compileMessagePlaceholders(string $message, array $replace = [], $removes = false)
    {
        $message = $removes ? $this->removePlaceholders($message, count($replace)) : $message;

        $silence = new CallSilencer(function () use ($message, $replace) {
            return vsprintf($message, $replace);
        }, function ($ret) {
            return $ret !== null && !empty($ret);
        });

        return $silence->invoke()->isResultValid() ? $silence->getResult() : false;
    }

    /**
     * Filters an array of parameters (the values passed to any of this object's variadic methods) of all throwables.
     *
     * @param mixed[] $from
     *
     * @return mixed[]
     */
    final private function filterNotThrowable(array $from) : array
    {
        $to = array_filter($from, function ($value) {
            return !ClassInfo::isThrowableEquitable($value);
        });

        return array_map(function ($value) {
            return $this->toScalarRepresentation($value);
        }, $to);
    }

    /**
     * Filters an array of parameters (the values passed to any of this object's variadic methods) of non-throwables
     * and returns the first found or null if none are found.
     *
     * @param mixed[] $from
     *
     * @return \Throwable[]
     */
    final private function filterThrowable(array $from)
    {
        return array_filter($from, function ($p) {
            return ClassInfo::isThrowableEquitable($p);
        });
    }

    /**
     * Returns a scalar representation of the passed value.
     *
     * @param mixed $value
     *
     * @return string
     */
    final private function toScalarRepresentation($value) : string
    {
        return is_scalar($value) ? $value : var_export($value);
    }

    /**
     * @return string
     */
    final private function attributesToString() : string
    {
        $attributes = $this->getAttributes();

        array_walk($attributes, function (&$value, $index) {
            $value = sprintf('[%s]=%s', $index, $this->toScalarRepresentation($value));
        });

        return implode(', ', $attributes);
    }

    /**
     * Replaces the message's replacement placeholders (used by {@see compileMessage()} (beginning with the nth found,
     * as defined by the startAt parameter) with a type representation of the expected value.
     *
     * @param string $message
     * @param int    $startAt
     *
     * @return string
     */
    final private function removePlaceholders(string $message, int $startAt = 0) : string
    {
        $regex = '{%([0-9-]+)?([sducoxXbgGeEfF])([0-9]?(?:\$[0-9]?[0-9]?[a-zA-Z]?)?)}';
        $count = 0;

        return preg_replace_callback($regex, function ($match) use ($startAt, &$count) {
            return ++$count > $startAt ? sprintf('<%s:null>', $this->expandPlaceholder($match[2])) : $match[0];
        }, $message);
    }

    /**
     * Expand placeholder (such as %s or %d) used in message to its full type name (such as string or integer).
     *
     * @param string $placeholder
     *
     * @return string
     */
    final private function expandPlaceholder($placeholder)
    {
        $typeName = 'unknown';
        $typeMaps = [
            'string' => ['s'],
            'integer' => ['d', 'u', 'c', 'o', 'x', 'X', 'b'],
            'double' => ['g', 'G', 'e', 'E', 'f', 'F'],
        ];

        foreach ($typeMaps as $name => $characters) {
            if (in_array($placeholder, $characters)) {
                $typeName = $name;
            }
        }

        return  $typeName;
    }

    /**
     * @return mixed[]
     */
    final private function contextArray()
    {
        try {
            return [
                $this->getContext(),
                $this->getContextClass(),
                $this->getContextMethod(),
                $this->getContextFileSnippet(),
            ];
        } catch (\Exception $e) {
            return [
                null,
                null,
                null,
                null,
            ];
        }
    }

    /**
     * Special, private method is only called by static create method (using reflection). Since on static construction
     * the actual exception instance is created within this class, the exception object properties (such as file, line)
     * refer to this file instead of the calling context. This method interprets a trace araay to reassign properties
     * to the location from which the create method was called.
     */
    final private function doContextReassignmentOnStaticInstantiation()
    {
        $priorCallTrace = array_slice($this->getTrace(), 1);

        if (count($priorCallTrace) > 0 && isset($priorCallTrace[0]['class']) && isset($priorCallTrace[0]['function'])) {
            $this->doContextReassignment($priorCallTrace[0]['class'], $priorCallTrace[0]['function']);
        }
    }

    /**
     * @param string $object
     * @param string $method
     */
    final private function doContextReassignment(string $object, string $method)
    {
        $object = new \ReflectionClass($object);

        $this->doContextReassignmentForProperty('file', $object->getFileName());
        $this->doContextReassignmentForProperty('line', $object->getMethod($method)->getStartLine());
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    final private function doContextReassignmentForProperty(string $name, $value)
    {
        $property = (new \ReflectionObject($this))
            ->getProperty($name);

        $property->setAccessible(true);
        $property->setValue($this, $value);
    }
}

/* EOF */
