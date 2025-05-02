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
use Reflector;
//use RuntimeException;
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
 *
 * This helper offer a variety of static method to get reflection-objects of
 * various types for given objects/class-strings.
 *
 * Instead of throwing exceptions, NULL is returned for failing scenarios
 */
class Reflection
{
    public const CACHE_ALL        = '*';
    public const CACHE_CLASSES    = ReflectionClass::class;
    public const CACHE_PROPERTIES = ReflectionProperty::class;
    public const CACHE_METHODS    = ReflectionMethod::class;
    public const CACHE_FUNCTIONS  = ReflectionFunction::class;
    public const CACHE_PARAMETERS = ReflectionParameter::class;

    private const EMPTY_CACHE = [
        self::CACHE_CLASSES    => [],
        self::CACHE_PROPERTIES => [],
        self::CACHE_METHODS    => [],
        self::CACHE_FUNCTIONS  => [],
        self::CACHE_PARAMETERS => [],
    ];

    /**
     * A cache of resolved reflection classes indexed by class name
     *
     * @var array<string, array<string, ReflectionFunction>>|
     *      array<string, array<string, ReflectionClass>>|
     *      array<string, array<string, array<string, ReflectionProperty|ReflectionMethod|true>>>|
     *      array<string, array<string, ReflectionParameter>|
     *      array<string, array<string, array<string, ReflectionParameter>>
     */
    private static $cache = self::EMPTY_CACHE;

    /**
     * Get the reflection class for given object/class-string
     *
     * @param object|string $objectOrClass An object or a class-string
     * @return ReflectionClass|null
     */
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

        if (class_exists($class)) {
            $rc = new ReflectionClass($objectOrClass);
            $cached_classes[$class] = $rc;
            return $rc;
        }

        return null;
    }

    /**
     * Get the FQCN for given object/class-string
     *
     * @param object|string $objectOrClass An object or a class-string
     * @param bool $check_existence Checl clas existence?
     * @return string|null
     */
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

    /**
     * Get the reflection properties defined for given object/class-string
     *
     * @param object|string $objectOrClass An object or a class-string
     * @return array|null An array of reflection-properties indexed by name or NULL
     */
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

    /**
     * Get the reflection-property for given object/class-string and property-name
     *
     * @param object|string $objectOrClass An object or a class-string
     * @param string $name The property-name
     * @return ReflectionProperty|null
     */
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

    /**
     * Get the reflection methods defined for given object/class-string
     *
     * @param object|string $objectOrClass An object or a class-string
     * @return array|null An array of reflection-methods indexed by name or NULL
     */
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

    /**
     * Get the reflection-method for given object/class-string and method-name
     *
     * @param object|string $objectOrClass An object or a class-string
     * @param string $name The method-name
     * @return ReflectionMethod|null
     */
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

    /**
     * Get the reflection-constructor for given object/class-string
     *
     * @param object|string $objectOrClass An object or a class-string
     * @return ReflectionMethod|null
     */
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
     * Get reflection-parameters for given (object/class-string, method-name) combination
     *
     * @param object|string $objectOrClass An object or a class-string
     * @param string $method The method-name
     * @param bool $check_existence Check method existence before using cache?
     * @return array|null
     */
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

    /**
     * Get reflection-parameters for the constructor of given object/class-string
     *
     * @param object|string $objectOrClass An object or a class-string
     * @param bool $check_existence Check constructor existence before using cache?
     * @return array|null
     */
    public static function getParametersForConstructor($objectOrClass, bool  $check_existence = true): ?array
    {
        return self::getParametersForMethod($objectOrClass, '__construct', $check_existence);
    }

    /**
     * Get reflection-parameters for given anonymous-function or invokable-object
     *
     * @param Closure|object $invokable
     * @return array|null
     */
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

    /**
     * Get reflection-parameters for a function-string
     *
     * @param string $function The fully-qualified function-name
     * @param bool $check_existence Check function existence before using cache?
     * @return array|null
     */
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

    /**
     * Clear the result cache
     *
     * @param string $type The cache key to clear, or '*' to clear all
     * @return void
     */
    public static function clearCache(string $type = self::CACHE_ALL): void
    {
        if ($type === self::CACHE_ALL) {
            self::$cache = self::EMPTY_CACHE;
        } elseif (!empty(self::$cache[$type])) {
            self::$cache[$type] = [];
        }
    }

    /**
     * Get the cached results for given type or for all types
     *
     * @internal Used for unit tests
     *
     * @param string|null $type
     * @return array|null
     */
    public static function getCache(?string $type = null): ?array
    {
        if (empty($type)) {
            return self::$cache;
        }

        return self::$cache[$type] ?? null;
    }
}
