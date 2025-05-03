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
//use Reflector;
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

    /**
     * A cache of resolved reflection-classes indexed by class-name
     * @var array<empty>|array<string, ReflectionClass>
     */
    private static $classes = []; // @phpstan-ignore-line https://github.com/phpstan/phpstan/issues/4078

    /**
     * A cache of resolved reflection-properties indexed by class-name and property-name
     * @var array<empty>|array<string, array<string, ReflectionProperty|true>>
     */
    private static $properties = [];

    /**
     * A cache of resolved reflection-methods indexed by class-name and method-name
     * @var array<empty>|array<string, array<string, ReflectionMethod|true>>
     */
    private static $methods = [];

    /**
     * A cache of resolved reflection-functions indexed by class-name
     * @var array<empty>|array<string, ReflectionFunction>
     */
    private static $functions = [];

    /**
     * A cache of resolved reflection-parameters indexed by class-string::method-key/function-name
     * @var array<empty>|array<string, ReflectionParameter>
     */
    private static $parameters = [];

    /**
     * Get the reflection class for given object/class-string
     *
     * @param object|class-string $objectOrClass An object or a class-string
     * @return ReflectionClass|null
     */
    public static function getClass($objectOrClass): ?ReflectionClass // @phpstan-ignore-line https://github.com/phpstan/phpstan/issues/4078
    {
        $class = self::getClassName($objectOrClass, false);
        if (empty($class)) {
            return null;
        }

        $rc = self::$classes[$class] ?? null;
        if ($rc instanceof ReflectionClass) {
            return $rc;
        }

        if (class_exists($class)) {
            $rc = new ReflectionClass($objectOrClass);
            self::$classes[$class] = $rc;
            return $rc;
        }

        return null;
    }

    /**
     * Get the FQCN for given object/class-string
     *
     * @param object|class-string $objectOrClass An object or a class-string
     * @param bool $check_existence Checl clas existence?
     * @return class-string|null
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
        return null; // @phpstan-ignore-line
    }

    /**
     * Get the reflection properties defined for given object/class-string
     *
     * @param object|class-string $objectOrClass An object or a class-string
     * @return array<string|ReflectionProperty>|null An array of reflection-properties indexed by name or NULL
     */
    public static function getProperties($objectOrClass): ?array
    {
        $rc = self::getClass($objectOrClass);
        if ($rc === null) {
            return null;
        }

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        $all_are_cached = self::$properties[$class][self::CACHE_ALL] ?? false;
        if ($all_are_cached) {
            $rps = self::$properties[$class];
            unset($rps[self::CACHE_ALL]); // Do not include the all-cached flag
            /** @var array<string|ReflectionProperty> $rps */
            return $rps;
        }

        $rps = [];
        foreach ($rc->getProperties() as $rp) {
            $rps[$rp->getName()] = $rp;
        }

        self::$properties[$class] = $rps;
        self::$properties[$class][self::CACHE_ALL] = true;

        return $rps;
    }

    /**
     * Get the reflection-property for given object/class-string and property-name
     *
     * @param object|class-string $objectOrClass An object or a class-string
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

        $rp = self::$properties[$class][$name] ?? null;
        if ($rp instanceof ReflectionProperty) {
            return $rp;
        }

        if ($rc->hasProperty($name)) {
            $rp = $rc->getProperty($name);
            // self::$properties[$class] ??= [];
            self::$properties[$class][$name] = $rp; // @phpstan-ignore-line Or uncomment line above
            return $rp;
        }

        return null;
    }

    /**
     * Get the reflection methods defined for given object/class-string
     *
     * @param object|class-string $objectOrClass An object or a class-string
     * @return array<string|ReflectionMethod>|null An array of reflection-methods indexed by name or NULL
     */
    public static function getMethods($objectOrClass): ?array
    {
        $rc = self::getClass($objectOrClass);
        if ($rc === null) {
            return null;
        }

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        $all_are_cached = self::$methods[$class][self::CACHE_ALL] ?? false;
        if ($all_are_cached) {
            $rms = self::$methods[$class];
            unset($rms[self::CACHE_ALL]); // Do not include the all-cached flag
            /** @var array<string|ReflectionMethod> $rms */
            return $rms;
        }

        $rms = [];
        foreach ($rc->getMethods() as $rm) {
            $rms[$rm->getName()] = $rm;
        }

        self::$methods[$class] = $rms;
        self::$methods[$class][self::CACHE_ALL] = true; // Set the all-cached flag

        return $rms;
    }

    /**
     * Get the reflection-method for given object/class-string and method-name
     *
     * @param object|class-string $objectOrClass An object or a class-string
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

        $rm = self::$methods[$class][$name] ?? null;
        if ($rm instanceof ReflectionMethod) {
            return $rm;
        }

        if ($rc->hasMethod($name)) {
            $rm = $name === '__construct' ? $rc->getConstructor() : $rc->getMethod($name);
            // self::$methods[$class] ??= [];
            self::$methods[$class][$name] = $rm; // @phpstan-ignore-line Or uncomment line above
            return $rm;
        }

        return null;
    }

    /**
     * Get the reflection-constructor for given object/class-string
     *
     * @param object|class-string $objectOrClass An object or a class-string
     * @return ReflectionMethod|null
     */
    public static function getConstructor($objectOrClass): ?ReflectionMethod
    {
        return self::getMethod($objectOrClass, '__construct');
    }

    public static function getFunction(string $function): ?ReflectionFunction
    {
        $rf = self::$functions[$function] ?? null;
        if ($rf instanceof ReflectionFunction) {
            return $rf;
        }

        if (function_exists($function)) {
            $rf = new ReflectionFunction($function);
            self::$functions[$function] = $rf;
            return $rf;
        }

        return null;
    }

    /**
     * Get reflection-parameters for given (object/class-string, method-name) combination
     *
     * @param object|class-string $objectOrClass An object or a class-string
     * @param string $method The method-name
     * @param bool $check_existence Check method existence before using cache?
     * @return array<int, ReflectionParameter>|null
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

        $cmkey = "{$class}::{$method}";

        // Try cached reflection parameters first, if any
        $parameters = self::$parameters[$cmkey] ?? null;
        if (is_array($parameters)) {
            return $parameters;
        }

        $rm = self::getMethod($objectOrClass, $method);
        if ($rm === null) {
            return null;
        }

        // Get and cache the parameters
        $parameters = $rm->getParameters();

        self::$parameters[$cmkey] = $parameters;

        return $parameters;
    }

    /**
     * Get reflection-parameters for the constructor of given object/class-string
     *
     * @param object|class-string $objectOrClass An object or a class-string
     * @param bool $check_existence Check constructor existence before using cache?
     * @return array<int, ReflectionParameter>|null
     */
    public static function getParametersForConstructor($objectOrClass, bool  $check_existence = true): ?array
    {
        return self::getParametersForMethod($objectOrClass, '__construct', $check_existence);
    }

    /**
     * Get reflection-parameters for given anonymous-function or invokable-object
     *
     * @param Closure|object $invokable
     * @return array<int, ReflectionParameter>|null
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
     * @return array<int, ReflectionParameter>|null
     */
    public static function getParametersForFunction(
        string $function,
        bool $check_existence = true
    ): ?array {
        if ($check_existence && !function_exists($function)) {
            return null;
        }

        $parameters = self::$parameters[$function] ?? null;
        if (is_array($parameters)) {
            return $parameters;
        }

        $rf = self::getFunction($function);
        if ($rf === null) {
            return null;
        }

        $parameters = $rf->getParameters();

        self::$parameters[$function] = $parameters;

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
        switch ($type) {
            case self::CACHE_ALL:
                self::$classes = [];
                self::$properties = [];
                self::$methods = [];
                self::$functions = [];
                self::$parameters = [];
                break;
            case self::CACHE_CLASSES:
                self::$classes = [];
                break;
            case self::CACHE_PROPERTIES:
                self::$properties = [];
                break;
            case self::CACHE_METHODS:
                self::$methods = [];
                break;
            case self::CACHE_FUNCTIONS:
                self::$functions = [];
                break;
            case self::CACHE_PARAMETERS:
                self::$parameters = [];
                break;
        }
    }

    /**
     * Get the cached results for given type or for all types
     *
     * @internal Used for unit tests
     *
     * @param string|null $type
     * @return array<string, mixed>|null
     */
    public static function getCache(?string $type = null): ?array
    {
        if (empty($type) || $type === self::CACHE_ALL) {
            return [
                self::CACHE_CLASSES    => self::$classes,
                self::CACHE_PROPERTIES => self::$properties,
                self::CACHE_METHODS    => self::$methods,
                self::CACHE_FUNCTIONS  => self::$functions,
                self::CACHE_PARAMETERS => self::$parameters,
            ];
        }

        switch ($type) {
            case self::CACHE_CLASSES:
                return self::$classes;
            case self::CACHE_PROPERTIES:
                return self::$properties;
            case self::CACHE_METHODS:
                return self::$methods;
            case self::CACHE_FUNCTIONS:
                return self::$functions;
            case self::CACHE_PARAMETERS:
                return self::$parameters;
        }

        return null;
    }
}
