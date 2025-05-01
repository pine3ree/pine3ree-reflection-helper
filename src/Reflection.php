<?php

/**
 * @package     pine3ree-reflection
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Helper;

use Closure;
//use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
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
 * A reflection helper class that support result caching
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

    public static function getProperties($objectOrClass, bool $cache_results = false): ?array
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

        if (!$cache_results) {
            return $rps;
        }

        // Cache values for declaring-class as well
        foreach ($rps as $name => $rp) {
            $dclass = $rp->getDeclaringClass()->getName();
            if ($dclass !== $class && empty($cached_properties[$dclass][$name])) {
                $cached_properties[$dclass][$name] = $rp;
            }
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
            $dclass = $rp->getDeclaringClass()->getName();
            if ($dclass !== $class && empty($cached_properties[$dclass][$name])) {
                $cached_properties[$dclass][$name] = $rp;
            }
            return $rp;
        }

        return null;
    }

    public static function getMethods($objectOrClass, bool $cache_results = false): ?array
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

        if (!$cache_results) {
            return $rms;
        }

        // Cache values for declaring-class as well
        foreach ($rms as $name => $rm) {
            $dclass = $rm->getDeclaringClass()->getName();
            if ($dclass !== $class && empty($cached_methods[$dclass][$name])) {
                $cached_methods[$dclass][$name] = $rm;
            }
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
            $dclass = $rm->getDeclaringClass()->getName();
            if ($dclass !== $class && empty($cached_methods[$dclass][$name])) {
                $cached_methods[$dclass][$name] = $rm;
            }
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

        if (!function_exists($function)) {
            return null;
        }

        $rf = new ReflectionFunction($function);

        $cache_functions[$function] = $rf;

        return $rf;
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
            return self::getParametersForCallableArrayExpression($callable);
        }

        // Case: invokable-object
        if (is_object($callable) && is_callable($callable)) {
            return self::getParametersForInvokableObject($callable, false);
        }

        // Case: function
        if (is_string($callable) && function_exists($callable)) {
            return self::getParametersForFunction($callable, false);
        }

        return null;
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

        if ($rc === null) {
            return null;
        }

        $class = is_string($object) ? $object : $rc->getName();

        // Use a reference to make code easier to read
        $cached_parameters =& self::$cache[self::CACHE_PARAMETERS];

        // Try cached reflection parameters first, if any
        $parameters = $cached_parameters[$class][$method] ?? null;
        if ($parameters === null) {
            $rm = self::getMethod($object, $method);
            if ($rm === null) {
                return null;
            }
            $parameters = $rm->getParameters();
            $cached_parameters[$class][$method] = $parameters;
        }

        return $parameters;
    }

    public static function getParametersForInvokableObject(object $object, bool $check_callable = true): ?array
    {
        if ($check_callable && !is_callable($object)) {
            throw new RuntimeException(
                "The provided `object` is not invokable!"
            );
        }

        // Anonymous/arrow function
        if ($object instanceof Closure) {
            $rf = new ReflectionFunction($object);
            return $rf->getParameters();
        }

        // Invokable object
        $method = '__invoke';
        if (method_exists($object, $method)) {
            return self::getParametersForMethod($object, $method, false);
//            $cached_parameters =& self::$cache[self::CACHE_PARAMETERS];
//            // Try cached reflection parameters first, if any
//            $class = get_class($object);
//            $parameters = $cached_parameters[$class][$method] ?? null;
//            if ($parameters === null) {
//                $rm = self::getMethod($object, $method);
//                if ($rm === null) {
//                    return null;
//                }
//                $dclass = $rm->getDeclaringClass()->getName();
//                if ($dclass === $class) {
//                    return null;
//                }
//                $parameters = $cached_parameters[$dclass][$method] ?? null;
//            }
//            if ($parameters === null) {
//                $parameters = $rm->getParameters();
//                $cached_parameters[$class][$method] = $parameters;
//                if ($dclass !== $class) {
//                    $cached_parameters[$dclass][$method] = $parameters;
//                }
//            }
//
//            return $parameters;
        }

        return null;
    }

    public static function getParametersForMethod($objectOrClass, string $method, bool $check_existence = true): ?array
    {
        $class = self::getClassName($objectOrClass, $check_existence);

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

        $dclass = $rm->getDeclaringClass()->getName();

        // Try cached reflection parameters in declaring class, if different
        if ($dclass !== $class) {
            $parameters = $cached_parameters[$dclass][$method] ?? null;
            if (is_array($parameters)) {
                $cached_parameters[$class][$method] =& $cached_parameters[$dclass][$method];
                return $parameters;
            }
        }

        // Get and cache the parameters
        $parameters = $rm->getParameters();
        $cached_parameters[$dclass][$method] = $parameters;
        if ($dclass !== $class) {
//            $cached_parameters[$class][$method] = $parameters;
            $cached_parameters[$class][$method] =& $cached_parameters[$dclass][$method];
        }

        return $parameters;
    }

    public static function getParametersForFunction(string $function, bool $check_existence = true): ?array
    {
        if ($check_existence && !function_exists($function)) {
            return null;
        }

        // Use a reference to make code easier to read
        $cached_parameters =& self::$cache[self::CACHE_PARAMETERS];

        $parameters = $cached_parameters[$function] ?? null;
        if ($parameters === null) {
            $rf = self::getFunction($function);
            if ($rf === null) {
                return null;
            }
            $parameters = $rf->getParameters();
            $cached_parameters[$function] = $parameters;
        }

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
     * @internal
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
