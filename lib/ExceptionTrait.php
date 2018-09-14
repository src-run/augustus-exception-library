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

use SR\Utilities\Query\ClassQuery;

trait ExceptionTrait
{
    use ExceptionAttributesTrait;
    use ExceptionContextTrait;
    use ExceptionInterpolateTrait;

    /**
     * Constructor accepts message string and any number of parameters, which will be used as string replacements for
     * message string (unless an instance of \Throwable is found, in which case it is passed to parent as previous).
     *
     * @param null|string $message
     * @param mixed       ...$parameters
     */
    public function __construct(string $message = null, ...$parameters)
    {
        parent::__construct($this->resolveMessage((string) $message, $parameters), null, $this->resolvePreviousException($parameters));
    }

    /**
     * Return string representation of exception.
     *
     * @return string
     */
    final public function __toString(): string
    {
        $message = vsprintf('%s: %s (in "%s" at "%s:%d").', [
            $this->getType(false),
            $this->getMessage(),
            $this->getContextMethodName(),
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
    final public function __toArray(): array
    {
        return [
            'type' => $this->getType(true),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->getContext(),
            'class' => $this->getContextClassName(),
            'method' => $this->getContextMethodName(),
            'file-name' => $this->getFile(),
            'file-line' => $this->getLine(),
            'file-diff' => $this->getContextFileSnippet(),
            'attributes' => $this->getAttributes(),
            'traceable' => function () {
                return $this->getTrace();
            },
        ];
    }

    /**
     * @param null|string $message
     * @param mixed       ...$parameters
     *
     * @return ExceptionInterface|ExceptionTrait
     */
    final public static function create(string $message = null, ...$parameters): ExceptionInterface
    {
        $instance = new static($message, ...$parameters);

        foreach (array_slice($instance->getTrace(), 1) as $step) {
            if (true === isset($step['class']) && true === isset($step['function'])) {
                self::assignInstancePropertiesFromTrace($instance, $step['class'], $step['function']);
                break;
            }
        }

        return $instance;
    }

    /**
     * Returns the exception type (class name) as either a fully-qualified class name or as just the class base name.
     *
     * @param bool $qualified
     *
     * @return string
     */
    final public function getType(bool $qualified = false): string
    {
        return $qualified
            ? ClassQuery::getNameQualified(static::class)
            : ClassQuery::getNameShort(static::class);
    }

    /**
     * @param object $instance
     * @param string $class
     * @param string $function
     */
    final private static function assignInstancePropertiesFromTrace($instance, string $class, string $function)
    {
        ClassQuery::setNonAccessiblePropertyValue(
            'file', $instance, ($rc = ClassQuery::getReflection($class))->getFileName()
        );

        ClassQuery::setNonAccessiblePropertyValue(
            'line', $instance, $rc->getMethod($function)->getStartLine()
        );
    }

    /**
     * @return string
     */
    final private function attributesToString(): string
    {
        $attributes = $this->getAttributes();

        array_walk($attributes, function (&$value, $name) {
            $value = sprintf('[%s]=%s', $name, $this->stringifyValue($value));
        });

        return implode(', ', $attributes);
    }
}
