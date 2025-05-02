<?php

/**
 * @package pine3ree-reflection
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Helper;

use Closure;
use ReflectionClass;
//use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;
//use Throwable;

use function class_exists;
use function function_exists;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;

/**
 * A reflection helper class with results caching
 */
class Reflection
{
    public static function getClass($objectOrClass): ?ReflectionClass
    {
        $class = self::getClassName($objectOrClass, true);
        if (empty($class)) {
            return null;
        }

        return new ReflectionClass($objectOrClass);
    }

    private static function getClassName($objectOrClass, bool $check_existence = true): ?string
    {
        if (empty($objectOrClass)) {
            return null;
        }
        if (is_object($objectOrClass)) {
            return get_class($objectOrClass);
        }
        if (is_string($objectOrClass)) {
            if ($check_existence && !class_exists($objectOrClass)) {
                return null;
            }
            return $objectOrClass;
        }
        return null;
    }

    public static function getProperties($objectOrClass): ?array
    {
        $rc = self::getClass($objectOrClass);
        if ($rc === null) {
            return null;
        }

        return $rc->getProperties();
    }

    public static function getProperty($objectOrClass, string $name): ?ReflectionProperty
    {
        $rc = self::getClass($objectOrClass);
        if ($rc === null) {
            return null;
        }

        if ($rc->hasProperty($name)) {
            return $rc->getProperty($name);
        }

        return null;
    }

    public static function getMethods($objectOrClass): ?array
    {
        $rc = self::getClass($objectOrClass);
        if ($rc === null) {
            return null;
        }

        return $rc->getMethods();
    }

    public static function getMethod($objectOrClass, string $name): ?ReflectionMethod
    {
        $rc = self::getClass($objectOrClass);
        if ($rc === null) {
            return null;
        }

        if ($rc->hasMethod($name)) {
            $rm = $name === '__construct' ? $rc->getConstructor() : $rc->getMethod($name);
            return $rm;
        }

        return null;
    }

    public static function getConstructor($objectOrClass): ?ReflectionMethod
    {
        return self::getMethod($objectOrClass, '__construct');
    }

    public static function getFunction(string $function): ?ReflectionFunction
    {
        if (function_exists($function)) {
            return new ReflectionFunction($function);
        }

        return null;
    }

    /**
     *
     * @param string|array{0: object|string, 1: string}|object $callable
     *      An [object/class, method] array expression, a function or an invokable
     *      object. Use [fqcn, '__construct'] for class constructors.
     * @return ReflectionParameter[]|null
     * @throws RuntimeException
     */
    public static function getParameters($callable): ?array
    {
        // Case: callable array expression [object/class, method]
        if (is_array($callable)) {
            $object = $callable[0] ?? null;
            $method = $callable[1] ?? null;
            if (empty($object)) {
                throw new RuntimeException(
                    "An empty object/class value was provided in element {0} of the callable array-expression!"
                );
            }
            if (empty($method) || !is_string($method)) {
                throw new RuntimeException(
                    "An empty/invalid method value was provided in element {1} of the callable array-expression!"
                );
            }
            return self::getParametersForMethod($object, $method);
        }

        // Case: closure/invokable-object
        if (is_object($callable)) {
            // Anonymous/arrow function (cannot be cached)
            if ($callable instanceof Closure) {
                $rf = new ReflectionFunction($callable);
                return $rf->getParameters();
            }
            // Invokable object
            if (method_exists($callable, $method = '__invoke')) {
                return self::getParametersForMethod($callable, $method);
            }
            return null;
        }

        // Case: function
        if (is_string($callable) && function_exists($callable)) {
            return self::getParametersForFunction($callable);
        }

        return null;
    }

    public static function getParametersForMethod($objectOrClass, string $method): ?array
    {
        $rm = self::getMethod($objectOrClass, $method);
        if (empty($rm)) {
            return null;
        }

        return $rm->getParameters();
    }

    public static function getParametersForFunction(string $function): ?array
    {
        $rf = self::getFunction($function);
        if ($rf === null) {
            return null;
        }

        return $rf->getParameters();
    }
}
