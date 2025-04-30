<?php

/**
 * @package     pine3ree-reflection
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Helper;

use Closure;
//use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;
use Throwable;
//use pine3ree\Container\ParamsResolverInterface;

use function class_exists;
use function function_exists;
use function get_class;
use function interface_exists;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;

/**
 * Class Reflection
 */
class Reflection
{

    public const CACHE_ALL        = '*';
    public const CACHE_CLASSES    = ReflectionClass::class;
    public const CACHE_PROPERTIES = ReflectionProperty::class;
//    public const CACHE_PROPERTY   = ReflectionProperty::class;
    public const CACHE_METHODS    = ReflectionMethod::class;
    public const CACHE_FUNCTIONS  = ReflectionFunction::class;
    public const CACHE_PARAMETERS = ReflectionParameter::class;

    private const EMPTY_CACHE = [
        self::CACHE_CLASSES => [],
        self::CACHE_PROPERTIES => [],
        self::CACHE_METHODS => [],
        self::CACHE_FUNCTIONS => [],
        self::CACHE_PARAMETERS => [],
    ];

    /**
     * A cache of resolved reflection classes indexed by class name
     *
     * @var array<string, array<string, ReflectionClass|ReflectionProperty|ReflectionMethod|ReflectionFunction|ReflectionParameter>>
     */
    private static $cache = self::EMPTY_CACHE;

    /**
     * A cache of resolved reflection classes indexed by class name
     *
     * @var array<string, ReflectionClass>
     */
    private static $classes = [];

    /**
     * A cache of resolved reflection propertis indexed by class name
     *
     * @var array<string, ReflectionReflectionProperty>
     */
    private static $properties = [];

    /**
     * A cache of resolved reflection classes indexed by class name
     *
     * @var array<string, ReflectionMethod>
     */
    private static $methods = [];

    /**
     * A cache of resolved reflection classes indexed by class name
     *
     * @var array<string, ReflectionFunction>
     */
    private static $functions = [];

    /**
     * A cache of resolved reflection parameters indexed by function/class::method name
     *
     * @var array<string, ReflectionParameter[]>
     */
    private static $parameters = [];

    public static function getClass($objectOrClass): ReflectionClass
    {
        if (is_object($objectOrClass)) {
            $class = get_class($objectOrClass);
        } elseif (is_string($objectOrClass)) {
            $class = $objectOrClass;
        } else {
            throw new RuntimeException(
                "The `objectOrClass` argument must be an object or a class-string"
            );
        }

        $rc = self::$cache[self::CACHE_CLASSES][$class] ?? null;
        if ($rc instanceof ReflectionClass) {
            return $rc;
        }

        if (!class_exists($class)) {
            throw new RuntimeException(
                "Unable to find a class named `{$class}`"
            );
        }

        $rc = new ReflectionClass($class);

        self::$cache[self::CACHE_CLASSES][$class] = $rc;

        return $rc;
    }

    public static function getProperties($objectOrClass): array
    {
        $rc = self::getClass($objectOrClass);

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        $cached = self::$cache[self::CACHE_PROPERTIES][$class][self::CACHE_ALL] ?? false;
        if ($cached === true) {
            $rps = self::$cache[self::CACHE_PROPERTIES][$class];
            unset($rps[self::CACHE_ALL]); // Do not include the all-cached flag
            return $rps;
        }

        $rps = [];
        foreach ($rc->getProperties() as $rp) {
            $rps[$rp->getName()] = $rp;
        }
        // Cache values for declaring-class as well
        foreach ($rps as $name => $rp) {
            if ($class !== $dclass = $rp->getDeclaringClass()->getName()) {
                self::$cache[self::CACHE_PROPERTIES][$dclass][$name] = $rp;
            }
        }

        $rps[self::CACHE_ALL] = true; // Set the all-cached flag

        self::$cache[self::CACHE_PROPERTIES][$class] = $rps;

        return $rps;
    }

    public static function getProperty($objectOrClass, string $name): ?ReflectionProperty
    {
        $rc = self::getClass($objectOrClass);

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        $rp = self::$cache[self::CACHE_PROPERTIES][$class][$name] ?? null;
        if ($rp instanceof ReflectionProperty) {
            return $rp;
        }

        if ($rc->hasProperty($name)) {
            $rp = $rc->getProperty($name);
            self::$cache[self::CACHE_PROPERTIES][$class][$name] = $rp;
            if ($class !== $dclass = $rp->getDeclaringClass()->getName()) {
                self::$cache[self::CACHE_PROPERTIES][$dclass][$name] = $rp;
            }
            return $rp;
        }

        return null;
    }

    public static function getMethods($objectOrClass): array
    {
        $rc = self::getClass($objectOrClass);

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        $cached = self::$cache[self::CACHE_METHODS][$class][self::CACHE_ALL] ?? false;
        if ($cached === true) {
            $rms = self::$cache[self::CACHE_METHODS][$class];
            unset($rms[self::CACHE_ALL]); // Do not include the all-cached flag
            return $rms;
        }

        $rms = [];
        foreach ($rc->getMethods() as $rm) {
            $rms[$rm->getName()] = $rm;
        }
        // Cache values for declaring-class as well
        foreach ($rms as $name => $rm) {
            if ($class !== $dclass = $rm->getDeclaringClass()->getName()) {
                self::$cache[self::CACHE_PROPERTIES][$dclass][$name] = $rm;
            }
        }

        $rms[self::CACHE_ALL] = true; // Set the all-cached flag

        self::$cache[self::CACHE_METHODS][$class] = $rms;

        return $rms;
    }

    public static function getMethod($objectOrClass, string $name): ?ReflectionMethod
    {
        $rc = self::getClass($objectOrClass);

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        $rm = self::$cache[self::CACHE_METHODS][$class][$name] ?? null;
        if ($rm instanceof ReflectionMethod) {
            return $rm;
        }

        if ($rc->hasMethod($name)) {
            $rm = $name === '__construct' ? $rc->getConstructor() : $rc->getMethod($name);
            self::$cache[self::CACHE_METHODS][$class][$name] = $rm;
            if ($class !== $dclass = $rm->getDeclaringClass()->getName()) {
                self::$cache[self::CACHE_METHODS][$dclass][$name] = $rm;
            }
            return $rm;
        }

        throw new RuntimeException(
            "A method named `{$class}::{$name}` has not been found!"
        );
    }

    public static function getConstructor($objectOrClass): ?ReflectionMethod
    {
        return self::getMethod($objectOrClass, '__construct');
    }

    public static function getFunction(string $function): ?ReflectionFunction
    {
//        $rf = self::$functions[$function] ?? null;
        $rf = self::$cache[self::CACHE_FUNCTIONS][$function] ?? null;
        if ($rf instanceof ReflectionFunction) {
            return $rf;
        }

        if (!function_exists($function)) {
            throw new RuntimeException(
                "Unable to find a function definition for `{$function}`"
            );
        }

        $rf = new ReflectionFunction($function);

        self::$cache[self::CACHE_FUNCTIONS][$function] = $rf;
        return $rf;
    }

    /**
     *
     * @param string|array{0: object|string, 1: string}|object $callable
     *      An [object/class, method] array expression, a function or an invokable
     *      object. Use [fqcn, '__construct'] for class constructors.
     * @return ReflectionParameter[]
     * @throws RuntimeException
     */
    public static function getParameters($callable): ?array
    {
        // Case: callable array expression [object/class, method]
        if (is_array($callable)) {
            return self::getParametersForCallableArrayExpression($callable);
        }

        // Case: invokable-object
        if (is_object($callable)) {
            return self::getParametersForInvokableObject($callable);
        }

        // Case: function
        if (is_string($callable) && function_exists($callable)) {
            return self::getParametersForFunction($callable, false);
        }

        throw new RuntimeException(
            "Cannot fetch a reflection method or function for given callable expression!"
        );
    }

    /**
     *
     * @param array{0: object|string, 1: string} $callable An [object/class, method] array expression
     * @return ReflectionParameter[]|null
     * @throws RuntimeException
     */
    private static function getParametersForCallableArrayExpression(array $callable): ?array
    {
        $object = $callable[0] ?? null;
        $method = $callable[1] ?? null;

        if (empty($method) || !is_string($method)) {
            throw new RuntimeException(
                "An invalid method value was provided in element {1} of the callable array-expression!"
            );
        }

        if (empty($object)) {
            throw new RuntimeException(
                "An empty object/class value was provided in element {0} of the callable array-expression!"
            );
        }

        $rc = self::getClass($object);

        $class = is_string($object) ? $object : $rc->getName();

        // Try cached reflection parameters first, if any
//        $cmkey = "{$class}::{$method}";
//        $parameters = self::$parameters[$cmkey] ?? null;
//        $parameters = self::$cache[self::CACHE_PARAMETERS][$cmkey] ?? null;
        $parameters = self::$cache[self::CACHE_PARAMETERS][$class][$method] ?? null;
        if ($parameters === null) {
            $rm = self::getMethod($object, $method);
            if ($rm instanceof ReflectionMethod) {
//                self::$parameters[$cmkey] = $parameters = $rm->getParameters();
//                self::$cache[self::CACHE_PARAMETERS][$cmkey] = $parameters = $rm->getParameters();
                self::$cache[self::CACHE_PARAMETERS][$class][$method] = $parameters = $rm->getParameters();
            }
        }

        return $parameters;
    }

    private static function getParametersForInvokableObject(object $object): ?array
    {
        if (!is_callable($object)) {
            throw new RuntimeException(
                "The provided `object` is not invokable!"
            );
        }

        // Case: anonymous/arrow function
        if ($object instanceof Closure) {
            $rf = new ReflectionFunction($object);
            return $rf->getParameters();
        }

        // Case: invokable object
        if (method_exists($object, '__invoke')) {
            /** @var object $callable Already ensured to be a an object by the conditional */
            // Try cached reflection parameters first, if any
            $class = get_class($object);
//            $cmkey = "{$class}::__invoke";
//            $parameters = self::$parameters[$cmkey] ?? null;
//            $parameters = self::$cache[self::CACHE_PARAMETERS][$cmkey] ?? null;
            $parameters = self::$cache[self::CACHE_PARAMETERS][$class]['__invoke'] ?? null;
            if ($parameters === null) {
                $rm = self::getMethod($object, '__invoke');
                $dclass = $rm->getDeclaringClass()->getName();
                $parameters = self::$cache[self::CACHE_PARAMETERS][$dclass]['__invoke'] ?? null;
            }
            if ($parameters === null) {
                $parameters = $rm->getParameters();
//                self::$parameters[$cmkey] = $parameters = $rm->getParameters();
//                self::$cache[self::CACHE_PARAMETERS][$cmkey] = $parameters = $rm->getParameters();
                self::$cache[self::CACHE_PARAMETERS][$class]['__invoke'] = $parameters;
                if ($dclass !== $class) {
                    self::$cache[self::CACHE_PARAMETERS][$dclass]['__invoke'] = $parameters;
                }
            }

            return $parameters;
        }

        throw new RuntimeException(
            "The provided `object` argument is an not invokable!"
        );
    }

    private static function getParametersForFunction(string $function, bool $check_existence = true): array
    {
        if ($check_existence && !function_exists($function)) {
            throw new RuntimeException(
                "The provided function `{$function}` is not defined!"
            );
        }

//        $parameters = self::$parameters[$function] ?? null;
        $parameters = self::$cache[self::CACHE_PARAMETERS][$function] ?? null;
        if ($parameters === null) {
            $rf = self::getFunction($function);
//            self::$parameters[$function] = $parameters = $rf->getParameters();
            self::$cache[self::CACHE_PARAMETERS][$function] = $parameters = $rf->getParameters();
        }

        return $parameters;
    }

    public static function getCacheKey($objectOrClass, ?string $method = null): string
    {
        if (is_object($objectOrClass)) {
            $class = get_class($objectOrClass);
        } elseif (is_string($objectOrClass)) {
            $class = $objectOrClass;
        } else {
            throw new RuntimeException(
                "Unable to create a cache-key"
            );
        }

        if (empty($method)) {
            return $class;
        }

        return "{$class}::{$method}";
    }

    public static function clearCache(string $target): void
    {
        if ($target === self::CACHE_ALL) {
            self::$cache = self::EMPTY_CACHE;
        } elseif (!empty(self::$cache[$target])) {
            self::$cache[$target] = [];
        }
//        unset(self::$cache[$target]);

        switch ($target) {
            case self::CACHE_ALL:
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

    public static function getCaches(): array
    {
        return self::$cache;
        $cache = [];
        foreach (self::$cache as $type => $cachedValues) {
            $cache[$type] = array_fill_keys(array_keys($cachedValues), '***');
        }
        return $cache;
        return [
            '$classes'    => self::$classes,
            '$properties' => self::$properties,
            '$methods'    => self::$methods,
            '$functions'  => self::$functions,
            '$parameters' => self::$parameters,
        ];
    }
}
