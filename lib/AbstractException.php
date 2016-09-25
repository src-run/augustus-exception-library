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
use SR\Util\Info\ClassInfo;

/**
 * The base, abstract exception class used by all concrete implementations.
 */
abstract class AbstractException extends \Exception implements ExceptionInterface
{
    /**
     * @var mixed[]
     */
    protected $attributes = [];

    /**
     * @var string
     */
    protected $message = null;

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     */
    public function __construct(string $message = null, ...$parameters)
    {
        parent::__construct($this->compileMessage($message, $parameters), 0, $this->filterOneThrowable($parameters));
    }

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     *
     * @return ExceptionInterface
     */
    public static function create(string $message = null, ...$parameters) : ExceptionInterface
    {
        $object = new static($message, ...$parameters);
        $method = (new \ReflectionObject($object))->getMethod('doContextReassignmentOnStaticInstantiation');
        $method->setAccessible(true);
        $method->invoke($object);

        return $object;
    }

    /**
     * @return string
     */
    protected function getMessageDefault() : string
    {
        return 'An unspecified exception was thrown during code execution';
    }

    /**
     * Returns the exception type (class name) as either a fully-qualified class name or as just the class base name.
     *
     * @param bool $fqcn
     *
     * @return string
     */
    final public function getType(bool $fqcn = false) : string
    {
        return $fqcn ? static::class : ClassInfo::getNameShort(static::class);
    }

    /**
     * Assign the exception message. All parameters following the first are treated as replacements for the first
     * parameter using {@see vsprintf}.
     *
     * @param string $message
     * @param mixed  ...$replacements
     *
     * @return ExceptionInterface
     */
    final public function setMessage(string $message = null, ...$replacements) : ExceptionInterface
    {
        $this->message = $this->compileMessage($message, $replacements);

        return $this;
    }

    /**
     * Assign an array of attributes to the exception.
     *
     * @param mixed[] $attributes
     *
     * @return ExceptionInterface
     */
    final public function setAttributes(array $attributes = []) : ExceptionInterface
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Returns an array of attributes.
     *
     * @return mixed[]
     */
    final public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * Returns true if any attributes exist.
     *
     * @return bool
     */
    final public function hasAttributes() : bool
    {
        return count($this->attributes) !== 0;
    }

    /**
     * Sets an attribute property using the index and value provided.
     *
     * @param string $index Index string
     * @param mixed  $value Value to set
     *
     * @return ExceptionInterface
     */
    final public function attribute(string $index, $value) : ExceptionInterface
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
     * Returns true if an attribute with the specified index exists.
     *
     * @param string $index The attribute index to search for
     *
     * @return bool
     */
    final public function hasAttribute(string $index) : bool
    {
        return array_key_exists((string) $index, $this->attributes) && !empty($this->attributes[(string) $index]);
    }

    /**
     * Return string representation of exception.
     *
     * @return string
     */
    final public function __toString() : string
    {
        $string = vsprintf('Exception "%s" with message "%s" in "%s" at line "%d"', [
            $this->getType(false),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
        ]);

        if ($this->hasAttributes()) {
            $string .= sprintf(' with attributes "%s"', $this->getAttributesAsString());
        }

        return $string;
    }

    /**
     * Return array representation of exception.
     *
     * @return mixed[]
     */
    final public function __toArray() : array
    {
        return [
            'type' => $this->getType(true),
            'class' => $this->getType(false),
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'code' => $this->getCode(),
            'attributes' => $this->getAttributes(),
            'traceable' => function () {
                return $this->getTrace();
            },
        ];
    }

    /**
     * Handle compilation of the final message using a string value and an optional array of replacements. This internal
     * function {@see vsprintf} is used, so reference it's documentation for acceptable placeholder syntax of the
     * string. Failure of the {@see vsprintf} call (which happens when, for example, the message string contains a
     * different number of placeholder than the number of replacements provided) will not fail or return null, but
     * instead return the message string in its un-compiled form.
     *
     * @param null|string $message
     * @param mixed[]     $replacements
     *
     * @return string|null
     */
    final private function compileMessage(string $message = null, array $replacements)
    {
        $message = $message ?: $this->getMessageDefault();
        $replace = $this->filterNotThrowable($replacements);

        if (false !== $compiled = $this->tryCompileMessage($message, $replace)) {
            return $this->cleanupMessagePlaceholders($compiled);
        }

        $message = $this->cleanupMessagePlaceholders($message, count($replace));
        $message = $this->tryCompileMessage($message, $replace) ?: $message;

        return $this->cleanupMessagePlaceholders($message);
    }

    /**
     * Try to compile message with provided string and replacement array.
     *
     * @param string  $message
     * @param mixed[] $replacements
     *
     * @return bool|string
     */
    final private function tryCompileMessage(string $message, array $replacements)
    {
        if (!empty($compiled = @vsprintf($message, $replacements)) && $compiled) {
            return $compiled;
        }

        return false;
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
     * @return \Throwable|null
     */
    final protected function filterOneThrowable(array $from)
    {
        $to = array_filter($from, function ($p) {
            return ClassInfo::isThrowableEquitable($p);
        });

        return count($to) === 0 ? null : array_shift($to);
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
    final private function getAttributesAsString() : string
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
    final private function cleanupMessagePlaceholders(string $message, int $startAt = 0) : string
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
