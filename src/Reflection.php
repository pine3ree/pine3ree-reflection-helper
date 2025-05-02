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
    public const CACHE_ALL        = '*';
    public const CACHE_CLASSES    = ReflectionClass::class;
    public const CACHE_DECLARING  = 'DeclaringClass';
    public const CACHE_PROPERTIES = ReflectionProperty::class;
    public const CACHE_METHODS    = ReflectionMethod::class;
    public const CACHE_FUNCTIONS  = ReflectionFunction::class;
    public const CACHE_PARAMETERS = ReflectionParameter::class;

    private const EMPTY_CACHE = [
        self::CACHE_CLASSES    => [],
        self::CACHE_DECLARING  => [],
        self::CACHE_PROPERTIES => [],
        self::CACHE_METHODS    => [],
        self::CACHE_FUNCTIONS  => [],
        self::CACHE_PARAMETERS => [],
    ];

    /**
     * A cache of resolved reflection classes indexed by class name
     *
     * @var array<string, array<string, ReflectionClass|ReflectionProperty|ReflectionMethod|ReflectionFunction|ReflectionParameter>>
     */
    private static $cache = self::EMPTY_CACHE;

    public static function getClass($objectOrClass): ?ReflectionClass
    {
        $class = self::getClassName($objectOrClass, false);
        if (empty($class)) {
            return null;
        }

        // Use a reference to make code easier to read
        $cached_classes =& self::$cache[self::CACHE_CLASSES];

        $rc = $cached_classes[$class] ?? null;
        if ($rc instanceof ReflectionClass) {
            return $rc;
        }

        if (!class_exists($class)) {
            return null;
        }

        $rc = new ReflectionClass($objectOrClass);

        $cached_classes[$class] = $rc;

        return $rc;
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

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        // Use a reference to make code easier to read
        $cached_properties =& self::$cache[self::CACHE_PROPERTIES];

        $all_are_cached = $cached_properties[$class][self::CACHE_ALL] ?? false;
        if ($all_are_cached) {
            $rps = $cached_properties[$class];
            unset($rps[self::CACHE_ALL]); // Do not include the all-cached flag
            return $rps;
        }

        $rps = [];
        foreach ($rc->getProperties() as $rp) {
            $rps[$rp->getName()] = $rp;
        }

        $cached_properties[$class] = $rps;
        $cached_properties[$class][self::CACHE_ALL] = true;

        return $rps;
    }

    public static function getProperty($objectOrClass, string $name): ?ReflectionProperty
    {
        $rc = self::getClass($objectOrClass);
        if ($rc === null) {
            return null;
        }

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        // Use a reference to make code easier to read
        $cached_properties =& self::$cache[self::CACHE_PROPERTIES];

        $rp = $cached_properties[$class][$name] ?? null;
        if ($rp instanceof ReflectionProperty) {
            return $rp;
        }

        if ($rc->hasProperty($name)) {
            $rp = $rc->getProperty($name);
            $cached_properties[$class][$name] = $rp;
            return $rp;
        }

        return null;
    }

    public static function getMethods($objectOrClass): ?array
    {
        $rc = self::getClass($objectOrClass);
        if ($rc === null) {
            return null;
        }

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        // Use a reference to make code easier to read
        $cached_methods =& self::$cache[self::CACHE_METHODS];

        $all_are_cached = $cached_methods[$class][self::CACHE_ALL] ?? false;
        if ($all_are_cached) {
            $rms = $cached_methods[$class];
            unset($rms[self::CACHE_ALL]); // Do not include the all-cached flag
            return $rms;
        }

        $rms = [];
        foreach ($rc->getMethods() as $rm) {
            $rms[$rm->getName()] = $rm;
        }

        $cached_methods[$class] = $rms;
        $cached_methods[$class][self::CACHE_ALL] = true; // Set the all-cached flag

        return $rms;
    }

    public static function getMethod($objectOrClass, string $name): ?ReflectionMethod
    {
        $rc = self::getClass($objectOrClass);
        if ($rc === null) {
            return null;
        }

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        // Use a reference to make code easier to read
        $cached_methods =& self::$cache[self::CACHE_METHODS];

        $rm = $cached_methods[$class][$name] ?? null;
        if ($rm instanceof ReflectionMethod) {
            return $rm;
        }

        if ($rc->hasMethod($name)) {
            $rm = $name === '__construct' ? $rc->getConstructor() : $rc->getMethod($name);
            $cached_methods[$class][$name] = $rm;
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
        $cache_functions =& self::$cache[self::CACHE_FUNCTIONS];

        $rf = $cache_functions[$function] ?? null;
        if ($rf instanceof ReflectionFunction) {
            return $rf;
        }

        if (function_exists($function)) {
            $rf = new ReflectionFunction($function);
            $cache_functions[$function] = $rf;
            return $rf;
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
            return self::getParametersForMethod($object, $method, true);
        }

        // Case: closure/invokable-object
        if (is_object($callable)) {
            return self::getParametersForInvokable($callable);
        }

        // Case: function
        if (is_string($callable) && function_exists($callable)) {
            return self::getParametersForFunction($callable, false);
        }

        return null;
    }

    public static function getParametersForMethod(
        $objectOrClass,
        string $method,
        bool $check_existence = true
    ): ?array {
        if (empty($method)) {
            return null;
        }

        $class = self::getClassName($objectOrClass, true);
        if (empty($class)) {
            return null;
        }

        if ($check_existence && !method_exists($class, $method)) {
            return null;
        }

        // Use a reference to make code easier to read
        $cached_parameters =& self::$cache[self::CACHE_PARAMETERS];

        // Try cached reflection parameters first, if any
        $parameters = $cached_parameters[$class][$method] ?? null;
        if (is_array($parameters)) {
            return $parameters;
        }

        $rm = self::getMethod($objectOrClass, $method);
        if ($rm === null) {
            return null;
        }

        // Get and cache the parameters
        $parameters = $rm->getParameters();

        $cached_parameters[$class][$method] = $parameters;

        return $parameters;
    }

    public static function getParametersForInvokable(object $invokable): ?array
    {
        // Anonymous/arrow function (cannot be cached)
        if ($invokable instanceof Closure) {
            return (new ReflectionFunction($invokable))->getParameters();
        }

        // Invokable object
        if (method_exists($invokable, $method = '__invoke')) {
            return self::getParametersForMethod($invokable, $method, false);
        }

        return null;
    }

    public static function getParametersForFunction(
        string $function,
        bool $check_existence = true
    ): ?array {
        if ($check_existence && !function_exists($function)) {
            return null;
        }

        // Use a reference to make code easier to read
        $cached_parameters =& self::$cache[self::CACHE_PARAMETERS];

        $parameters = $cached_parameters[$function] ?? null;
        if (is_array($parameters)) {
            return $parameters;
        }

        $rf = self::getFunction($function);
        if ($rf === null) {
            return null;
        }

        $parameters = $rf->getParameters();

        $cached_parameters[$function] = $parameters;

        return $parameters;
    }

    public static function clearCache(string $target): void
    {
        if ($target === self::CACHE_ALL) {
            self::$cache = self::EMPTY_CACHE;
        } elseif (!empty(self::$cache[$target])) {
            self::$cache[$target] = [];
        }
    }

    /**
     * @internal Used for unit tests
     *
     * @param string|null $target
     * @return array|null
     */
    public static function getCache(?string $target = null): ?array
    {
        if (empty($target)) {
            return self::$cache;
        }

        return self::$cache[$target] ?? null;
    }
}
