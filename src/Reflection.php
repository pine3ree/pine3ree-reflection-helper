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
 * Class Reflection
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
        if (is_object($objectOrClass)) {
            $class = get_class($objectOrClass);
        } elseif (is_string($objectOrClass)) {
            $class = $objectOrClass;
        } else {
            return null;
        }

        $rc = self::$cache[self::CACHE_CLASSES][$class] ?? null;
        if ($rc instanceof ReflectionClass) {
            return $rc;
        }

        if (!class_exists($class)) {
            return null;
        }

        $rc = new ReflectionClass($objectOrClass);

        self::$cache[self::CACHE_CLASSES][$class] = $rc;

        return $rc;
    }

    public static function getProperties($objectOrClass): ?array
    {
        $rc = self::getClass($objectOrClass);

        if ($rc === null) {
            return null;
        }

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        $cache_properties =& self::$cache[self::CACHE_PROPERTIES];

        $all_are_cached = $cache_properties[$class][self::CACHE_ALL] ?? false;
        if ($all_are_cached) {
            $rps = $cache_properties[$class];
            unset($rps[self::CACHE_ALL]); // Do not include the all-cached flag
            return $rps;
        }

        $rps = [];
        foreach ($rc->getProperties() as $rp) {
            $rps[$rp->getName()] = $rp;
        }
        // Cache values for declaring-class as well
        foreach ($rps as $name => $rp) {
            $dclass = $rp->getDeclaringClass()->getName();
            if ($dclass !== $class && empty($cache_properties[$dclass][$name])) {
                $cache_properties[$dclass][$name] = $rp;
            }
        }

        $cache_properties[$class] = $rps;
        $cache_properties[$class][self::CACHE_ALL] = true;

        return $rps;
    }

    public static function getProperty($objectOrClass, string $name): ?ReflectionProperty
    {
        $rc = self::getClass($objectOrClass);

        if ($rc === null) {
            return null;
        }

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        $cache_properties =& self::$cache[self::CACHE_PROPERTIES];

        $rp = $cache_properties[$class][$name] ?? null;
        if ($rp instanceof ReflectionProperty) {
            return $rp;
        }

        if ($rc->hasProperty($name)) {
            $rp = $rc->getProperty($name);
            $cache_properties[$class][$name] = $rp;
            $dclass = $rp->getDeclaringClass()->getName();
            if ($dclass !== $class && empty($cache_properties[$dclass][$name])) {
                $cache_properties[$dclass][$name] = $rp;
            }
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

        $cache_methods =& self::$cache[self::CACHE_METHODS];

        $all_are_cached = $cache_methods[$class][self::CACHE_ALL] ?? false;
        if ($all_are_cached) {
            $rms = $cache_methods[$class];
            unset($rms[self::CACHE_ALL]); // Do not include the all-cached flag
            return $rms;
        }

        $rms = [];
        foreach ($rc->getMethods() as $rm) {
            $rms[$rm->getName()] = $rm;
        }
        // Cache values for declaring-class as well
        foreach ($rms as $name => $rm) {
            $dclass = $rm->getDeclaringClass()->getName();
            if ($dclass !== $class && empty($cache_methods[$dclass][$name])) {
                $cache_methods[$dclass][$name] = $rm;
            }
        }

        $cache_methods[$class] = $rms;
        $cache_methods[$class][self::CACHE_ALL] = true; // Set the all-cached flag

        return $rms;
    }

    public static function getMethod($objectOrClass, string $name): ?ReflectionMethod
    {
        $rc = self::getClass($objectOrClass);

        if ($rc === null) {
            return null;
        }

        $class = is_string($objectOrClass) ? $objectOrClass : $rc->getName();

        $cache_methods =& self::$cache[self::CACHE_METHODS];

        $rm = $cache_methods[$class][$name] ?? null;
        if ($rm instanceof ReflectionMethod) {
            return $rm;
        }

        if ($rc->hasMethod($name)) {
            $rm = $name === '__construct' ? $rc->getConstructor() : $rc->getMethod($name);
            $cache_methods[$class][$name] = $rm;
            $dclass = $rm->getDeclaringClass()->getName();
            if ($dclass !== $class && empty($cache_methods[$dclass][$name])) {
                $cache_methods[$dclass][$name] = $rm;
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
        if (is_object($callable)) {
            return self::getParametersForInvokableObject($callable);
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

        // Try cached reflection parameters first, if any
        $cache_parameters =& self::$cache[self::CACHE_PARAMETERS];

        $parameters = $cache_parameters[$class][$method] ?? null;
        if ($parameters === null) {
            $rm = self::getMethod($object, $method);
            if ($rm instanceof ReflectionMethod) {
                $parameters = $rm->getParameters();
                $cache_parameters[$class][$method] = $parameters;
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
            $cache_parameters =& self::$cache[self::CACHE_PARAMETERS];
            // Try cached reflection parameters first, if any
            $class = get_class($object);
            $parameters = $cache_parameters[$class]['__invoke'] ?? null;
            if ($parameters === null) {
                $rm = self::getMethod($object, '__invoke');
                if ($rm === null) {
                    return null;
                }
                $dclass = $rm->getDeclaringClass()->getName();
                $parameters = $cache_parameters[$dclass]['__invoke'] ?? null;
            }
            if ($parameters === null) {
                $parameters = $rm->getParameters();
                $cache_parameters[$class]['__invoke'] = $parameters;
                if ($dclass !== $class) {
                    $cache_parameters[$dclass]['__invoke'] = $parameters;
                }
            }

            return $parameters;
        }

        return null;
    }

    private static function getParametersForFunction(string $function, bool $check_existence = true): ?array
    {
        if ($check_existence && !function_exists($function)) {
            return null;
        }

         $cache_parameters =& self::$cache[self::CACHE_PARAMETERS];

        $parameters = $cache_parameters[$function] ?? null;
        if ($parameters === null) {
            $rf = self::getFunction($function);
            if ($rf === null) {
                return null;
            }
            $parameters = $rf->getParameters();
            $cache_parameters[$function] = $parameters;
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
