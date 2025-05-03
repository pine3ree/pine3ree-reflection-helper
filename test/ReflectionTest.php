<?php

/**
 * @package    pine3ree-reflection
 * @subpackage pine3ree-reflection-test
 * @author     pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Helper;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DirectoryIterator;
use PHPUnit\Framework\TestCase;
//use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;
use pine3ree\Helper\Reflection;

use pine3ree\test\Helper\Asset\Foo;
use pine3ree\test\Helper\Asset\Bar;

use const PHP_VERSION_ID;

use function strtoupper;
use function time;


final class ReflectionTest extends TestCase
{
    protected function setUp(): void
    {
        Reflection::clearCache();
    }

    public function testGetClass()
    {
        Reflection::clearCache();

        // Positive for existing class
        $rc = Reflection::getClass(Foo::class);
        self::assertInstanceOf(ReflectionClass::class, $rc);
        self::assertEquals(Foo::class, $rc->getName());

        // Test cache
        $cache = Reflection::getCache(Reflection::CACHE_CLASSES);

        self::assertSame(Reflection::getClass(Foo::class), Reflection::getClass(Foo::class));
        self::assertSame(Reflection::getClass(Foo::class), Reflection::getClass(new Foo('foo')));

        // Negative lookup
        self::assertNull(Reflection::getClass(''));
        self::assertNull(Reflection::getClass(123));
        self::assertNull(Reflection::getClass(1.23));
        self::assertNull(Reflection::getClass([]));
    }

    public function testThatGetClassReturnsReflectionClassForExistingClass()
    {
        $rc = Reflection::getClass(Foo::class);
        self::assertInstanceOf(ReflectionClass::class, $rc);
        self::assertEquals(Foo::class, $rc->getName());
    }

    public function testThatResolvedReflectionClassesAreCached()
    {
        self::assertSame(Reflection::getClass(Foo::class), Reflection::getClass(Foo::class));
    }

    public function testThatGetClassReturnsNullForEmptyClass()
    {
        self::assertNull(Reflection::getClass(''));
    }

    public function testGetClassName()
    {
        $getClassName = new ReflectionMethod(Reflection::class, 'getClassName');
        $getClassName->setAccessible(true);

        // Success
        self::assertEquals(Foo::class, $getClassName->invoke(null, Foo::class, true));

        // Failure
        self::assertNull($getClassName->invoke(null, NonExistentClass::class, true));
    }

    /**
     * @dataProvider provideInvalidClassNames
     */
    public function testThatGetClassNameReturnsNullForInvalidObjectOrClass($invalidClassName)
    {
        $getClassName = new ReflectionMethod(Reflection::class, 'getClassName');
        $getClassName->setAccessible(true);

        self::assertNull($getClassName->invoke(null, $invalidClassName, true));
    }

    public function provideInvalidClassNames(): array
    {
        return [
            [null],
            [false],
            [123],
            [12.3],
            [[1, 2, 3]],
        ];
    }

    public function testThatGettingAnythingFromNonExistentClassReturnsNull()
    {
        self::assertNull(Reflection::getClass(NonExistentClass::class));
        self::assertNull(Reflection::getProperties(NonExistentClass::class));
        self::assertNull(Reflection::getProperty(NonExistentClass::class, 'someProperty'));
        self::assertNull(Reflection::getMethods(NonExistentClass::class));
        self::assertNull(Reflection::getMethod(NonExistentClass::class, 'someMethod'));
    }

    public function testGetProperties()
    {
        Reflection::clearCache();

        $cache = Reflection::getCache(Reflection::CACHE_PROPERTIES);

        self::assertIsArray($cache);
        self::assertEmpty($cache);

        $props = Reflection::getProperties(Foo::class);
        $myNameProp = Reflection::getProperty(Foo::class, 'myName');

        self::assertIsArray($props);
        self::assertArrayHasKey('myName', $props);

        foreach ($props as $name => $prop) {
            self::assertIsString($name);
            self::assertInstanceOf(ReflectionProperty::class, $myNameProp);
        }

        // Test cache
        $cache = Reflection::getCache(Reflection::CACHE_PROPERTIES);

        self::assertSame($props, Reflection::getProperties(Foo::class));

        self::assertIsArray($cache);
        self::assertNotEmpty($cache);
        self::assertArrayHasKey(Foo::class, $cache);
        self::assertIsArray($cache[Foo::class]);
        self::assertArrayHasKey(Reflection::CACHE_ALL, $cache[Foo::class]);
        self::assertSame($props + [Reflection::CACHE_ALL => true], $cache[Foo::class]);

        self::assertArrayHasKey('myName', $cache[Foo::class]);
        self::assertSame($myNameProp, $cache[Foo::class]['myName']);
    }

    public function testGetProperty()
    {
        Reflection::clearCache();

        $cache = Reflection::getCache(Reflection::CACHE_PROPERTIES);

        self::assertIsArray($cache);
        self::assertEmpty($cache);

        $myNameProp = Reflection::getProperty(Foo::class, 'myName');

        self::assertInstanceOf(ReflectionProperty::class, $myNameProp);

        // Test cache
        $cache = Reflection::getCache(Reflection::CACHE_PROPERTIES);

        self::assertIsArray($cache);
        self::assertNotEmpty($cache);
        self::assertArrayHasKey(Foo::class, $cache);

        self::assertArrayHasKey('myName', $cache[Foo::class]);
        self::assertSame($myNameProp, $cache[Foo::class]['myName']);
        self::assertSame($myNameProp, Reflection::getProperty(Foo::class, 'myName'));

        // Negative lookup
        self::assertNull(Reflection::getProperty(Foo::class, 'nonExistent'));
        self::assertNull(Reflection::getProperty(Foo::class, ''));
    }

    public function testGetMethods()
    {
        Reflection::clearCache();

        $methods = Reflection::getMethods(Foo::class);
        $getNameMethod = Reflection::getMethod(Foo::class, 'getName');

        self::assertIsArray($methods);
        self::assertArrayHasKey('__construct', $methods);
        self::assertArrayHasKey('getName', $methods);

        foreach ($methods as $name => $method) {
            self::assertIsString($name);
            self::assertInstanceOf(ReflectionMethod::class, $method);
        }

        // Test cache
        $cache = Reflection::getCache(Reflection::CACHE_METHODS);

        self::assertSame($methods, Reflection::getMethods(Foo::class));

        self::assertIsArray($cache);
        self::assertNotEmpty($cache);
        self::assertArrayHasKey(Foo::class, $cache);
        self::assertIsArray($cache[Foo::class]);
        self::assertArrayHasKey(Reflection::CACHE_ALL, $cache[Foo::class]);
        self::assertSame($methods + [Reflection::CACHE_ALL => true], $cache[Foo::class]);

        self::assertArrayHasKey('getName', $cache[Foo::class]);
        self::assertSame($getNameMethod, $cache[Foo::class]['getName']);
    }

    public function testGetMethod()
    {
        Reflection::clearCache();

        $cache = Reflection::getCache(Reflection::CACHE_METHODS);

        self::assertIsArray($cache);
        self::assertEmpty($cache);

        $getNameMethod = Reflection::getMethod(Foo::class, 'getName');

        self::assertInstanceOf(ReflectionMethod::class, $getNameMethod);

        // Test cache
        $cache = Reflection::getCache(Reflection::CACHE_METHODS);

        self::assertIsArray($cache);
        self::assertNotEmpty($cache);
        self::assertArrayHasKey(Foo::class, $cache);

        self::assertArrayHasKey('getName', $cache[Foo::class]);
        self::assertSame($getNameMethod, $cache[Foo::class]['getName']);
        self::assertSame($getNameMethod, Reflection::getMethod(Foo::class, 'getName'));

        // Negative lookup
        self::assertNull(Reflection::getMethod(Foo::class, 'nonExistent'));
        self::assertNull(Reflection::getMethod(Foo::class, ''));
    }

    public function testGetConstructor()
    {
        $foo = new Foo('foo');

        $ctor = Reflection::getConstructor($foo);

        $methodName = '__construct';

        self::assertTrue($ctor->isConstructor());

        self::assertSame($ctor, Reflection::getConstructor(Foo::class));
        self::assertSame($ctor, Reflection::getMethod($foo, $methodName));
        self::assertSame($ctor, Reflection::getMethod(Foo::class, $methodName));
    }

    public function testClosure()
    {
        $func = function () {
            return 42;
        };

        $rm = Reflection::getMethod($func, '__invoke');
        self::assertInstanceOf(ReflectionMethod::class, $rm);

        $rf = new ReflectionFunction($func);
        self::assertInstanceOf(ReflectionFunction::class, $rf);
    }

    public function testGetFunction()
    {
        Reflection::clearCache();

        $rf1 = Reflection::getFunction('strtoupper');
        $rf2 = Reflection::getFunction('strtolower');

        self::assertInstanceOf(ReflectionFunction::class, $rf1);
        self::assertInstanceOf(ReflectionFunction::class, $rf2);

        // Test cache
        $cache = Reflection::getCache(Reflection::CACHE_FUNCTIONS);

        self::assertIsArray($cache);
        self::assertNotEmpty($cache);

        self::assertArrayHasKey('strtoupper', $cache);
        self::assertArrayHasKey('strtolower', $cache);
        self::assertSame($rf1, $cache['strtoupper']);
        self::assertSame($rf2, $cache['strtolower']);

        // Negative lookup
        self::assertNull(Reflection::getFunction('nonExistentFunction'));
        self::assertNull(Reflection::getFunction(''));
    }

    public function testGetParametersForMethod()
    {
        $foo = new Foo();

        $params = Reflection::getParametersForMethod($foo, 'getName');

        self::assertIsArray($params);

        foreach ($params as $param) {
            self::assertInstanceOf(ReflectionParameter::class, $param);
        }

        // Test cache
        $cache = Reflection::getCache(Reflection::CACHE_PARAMETERS);
        self::assertSame($params, Reflection::getParametersForMethod($foo, 'getName'));


        // Negative lookups
        self::assertNull(Reflection::getParametersForMethod($foo, ''));
        self::assertNull(Reflection::getParametersForMethod(NonExistentClass::class, 'someMethod', true));
        self::assertNull(Reflection::getParametersForMethod(Foo::class, 'nonExistentMethod'));
        self::assertNull(Reflection::getParametersForMethod(Foo::class, 'nonExistentMethod', true));
        self::assertNull(Reflection::getParametersForMethod(Foo::class, 'nonExistentMethod', false));
    }

    public function testGetParametersForInvokable()
    {
        $foo = new Foo(); // not-invokable
        $bar = new Bar(); // invokable
        // Closure
        $fnc = function () {
            return 42;
        };

        $params1 = Reflection::getParametersForInvokable($bar);
        $params2 = Reflection::getParametersForInvokable($fnc);

        self::assertIsArray($params1);
        self::assertIsArray($params2);

        foreach ($params1 as $param) {
            self::assertInstanceOf(ReflectionParameter::class, $param);
        }
        foreach ($params2 as $param) {
            self::assertInstanceOf(ReflectionParameter::class, $param);
        }

        // Test cache
        $cache = Reflection::getCache(Reflection::CACHE_PARAMETERS);

        self::assertSame($params1, Reflection::getParametersForInvokable($bar));
        self::assertSame($params2, Reflection::getParametersForInvokable($fnc));

        // Negative lookups
        self::assertNull(Reflection::getParametersForInvokable($foo));
    }

    public function testGetParametersForFunction()
    {
        $foo = new Foo();

        $params = Reflection::getParametersForFunction('strtoupper');

        self::assertIsArray($params);

        foreach ($params as $param) {
            self::assertInstanceOf(ReflectionParameter::class, $param);
        }

        // Test cache
        $cache = Reflection::getCache(Reflection::CACHE_PARAMETERS);
        self::assertSame($params, Reflection::getParametersForFunction('strtoupper'));


        // Negative lookups
        self::assertNull(Reflection::getParametersForFunction('nonExistentFunction'));
        self::assertNull(Reflection::getParametersForFunction('nonExistentFunction', true));
        self::assertNull(Reflection::getParametersForFunction('nonExistentFunction', false));
    }

    public function testCacheStructureIsCorrect()
    {
        Reflection::getProperties(ArrayObject::class, true);
        Reflection::getMethods(ArrayObject::class, true);
        Reflection::getParametersForMethod(ArrayObject::class, 'offsetSet', true);
        Reflection::getParametersForConstructor(ArrayObject::class, true);

        Reflection::getFunction('htmlspecialchars');
        Reflection::getParametersForFunction('htmlspecialchars', true);

        $types = [
            ReflectionClass::class,
            ReflectionProperty::class,
            ReflectionMethod::class,
            ReflectionFunction::class,
            ReflectionParameter::class,
        ];

        $cache = Reflection::getCache();

        foreach ($cache as $type => $type_cache) {
            self::assertIsString($type);
            self::assertContains($type, $types);
            self::assertIsArray($type_cache);
            foreach ($type_cache as $name => $value) {
                self::assertIsString($name); // function/class/method/property name
                if ($type === Reflection::CACHE_CLASSES) {
                    self::assertInstanceOf(ReflectionClass::class, $value); // $value is an array of ReflectionProperty
                    continue;
                }
                if ($type === Reflection::CACHE_PROPERTIES) {
                    self::assertIsArray($value); // $value is an array of ReflectionProperty
                    foreach ($value as $key => $obj) {
                        self::assertIsString($key); // $key is the property-name
                        if ($key === Reflection::CACHE_ALL) {
                            self::assertEquals(true, $obj);
                        } else {
                            self::assertInstanceOf(ReflectionProperty::class, $obj);
                        }
                    }
                    continue;
                }
                if ($type === Reflection::CACHE_METHODS) {
                    self::assertIsArray($value); // $value is an array of ReflectionMethod
                    foreach ($value as $key => $val) {
                        self::assertIsString($key); // $key is the property-name
                        if ($key === Reflection::CACHE_ALL) {
                            self::assertEquals(true, $val);
                        } else {
                            self::assertInstanceOf(ReflectionMethod::class, $val);
                        }
                    }
                    continue;
                }
                if ($type === Reflection::CACHE_FUNCTIONS) {
                    self::assertInstanceOf(ReflectionFunction::class, $value);
                    continue;
                }
                if ($type === Reflection::CACHE_PARAMETERS) {
                    self::assertIsArray($value); // $value is an array of ReflectionProperty
                    foreach ($value as $key => $val) {
                        // function-paramters
                        if (is_int($key)) {
                            self::assertInstanceOf(ReflectionParameter::class, $val);
                            continue;
                        }
                        // $key is a method-name
                        if (is_string($key)) {
                            self::assertIsArray($val);
                            foreach ($val as $k => $v) {
                                self::assertIsInt($k); // $key is the property-name
                                self::assertInstanceOf(ReflectionParameter::class, $v);
                            }
                            continue;
                        }
                    }
                    continue;
                }
            }
        }
    }

    public function testGetCacheRetunrììrnsNullForInvalidType()
    {
        self::assertNull(Reflection::getCache('nonExistent'));
    }

    public function testClearCache()
    {
        $foo = new Foo('foo');
        $bar = new Bar('bar');

        // Fill-up the cache
        Reflection::getClass($foo);
        Reflection::getProperties($foo);
        Reflection::getMethods($foo);
        Reflection::getFunction('strtoupper');
        Reflection::getParametersForMethod($foo, '__construct');
        Reflection::getParametersForFunction('strtoupper');
        Reflection::getParametersForInvokable($bar);

        $cache = Reflection::getCache();

        self::assertIsArray($cache);
        self::assertNotEmpty($cache);

        self::assertArrayHasKey(Reflection::CACHE_CLASSES, $cache);
        self::assertArrayHasKey(Reflection::CACHE_PROPERTIES, $cache);
        self::assertArrayHasKey(Reflection::CACHE_METHODS, $cache);
        self::assertArrayHasKey(Reflection::CACHE_FUNCTIONS, $cache);
        self::assertArrayHasKey(Reflection::CACHE_PARAMETERS, $cache);

        self::assertIsArray($cache[Reflection::CACHE_CLASSES]);
        self::assertIsArray($cache[Reflection::CACHE_PROPERTIES]);
        self::assertIsArray($cache[Reflection::CACHE_METHODS]);
        self::assertIsArray($cache[Reflection::CACHE_FUNCTIONS]);
        self::assertIsArray($cache[Reflection::CACHE_PARAMETERS]);

        self::assertNotEmpty($cache[Reflection::CACHE_CLASSES]);
        self::assertNotEmpty($cache[Reflection::CACHE_PROPERTIES]);
        self::assertNotEmpty($cache[Reflection::CACHE_METHODS]);
        self::assertNotEmpty($cache[Reflection::CACHE_FUNCTIONS]);
        self::assertNotEmpty($cache[Reflection::CACHE_PARAMETERS]);

        Reflection::clearCache(Reflection::CACHE_CLASSES);
        $cache = Reflection::getCache();
        self::assertIsArray($cache[Reflection::CACHE_CLASSES]);
        self::assertEmpty($cache[Reflection::CACHE_CLASSES]);

        Reflection::clearCache(Reflection::CACHE_PROPERTIES);
        $cache = Reflection::getCache();
        self::assertIsArray($cache[Reflection::CACHE_PROPERTIES]);
        self::assertEmpty($cache[Reflection::CACHE_PROPERTIES]);

        Reflection::clearCache(Reflection::CACHE_METHODS);
        $cache = Reflection::getCache();
        self::assertIsArray($cache[Reflection::CACHE_METHODS]);
        self::assertEmpty($cache[Reflection::CACHE_METHODS]);

        Reflection::clearCache(Reflection::CACHE_FUNCTIONS);
        $cache = Reflection::getCache();
        self::assertIsArray($cache[Reflection::CACHE_FUNCTIONS]);
        self::assertEmpty($cache[Reflection::CACHE_FUNCTIONS]);

        Reflection::clearCache(Reflection::CACHE_PARAMETERS);
        $cache = Reflection::getCache();
        self::assertIsArray($cache[Reflection::CACHE_PARAMETERS]);
        self::assertEmpty($cache[Reflection::CACHE_PARAMETERS]);

        // Test clearCache() with argument
        Reflection::getClass($foo);
        Reflection::getProperties($foo);
        Reflection::getMethods($foo);
        Reflection::getFunction('strtoupper');
        Reflection::getParametersForMethod($foo, '__construct');
        Reflection::getParametersForFunction('strtoupper');
        Reflection::getParametersForInvokable($bar);

        $cache = Reflection::getCache();

        self::assertNotEmpty($cache[Reflection::CACHE_CLASSES]);
        self::assertNotEmpty($cache[Reflection::CACHE_PROPERTIES]);
        self::assertNotEmpty($cache[Reflection::CACHE_METHODS]);
        self::assertNotEmpty($cache[Reflection::CACHE_FUNCTIONS]);
        self::assertNotEmpty($cache[Reflection::CACHE_PARAMETERS]);

        Reflection::clearCache(Reflection::CACHE_ALL);
        $cache = Reflection::getCache();

        self::assertEmpty($cache[Reflection::CACHE_CLASSES]);
        self::assertEmpty($cache[Reflection::CACHE_PROPERTIES]);
        self::assertEmpty($cache[Reflection::CACHE_METHODS]);
        self::assertEmpty($cache[Reflection::CACHE_FUNCTIONS]);
        self::assertEmpty($cache[Reflection::CACHE_PARAMETERS]);

        // Test clearCache() without argument
        Reflection::getClass($foo);
        Reflection::getProperties($foo);
        Reflection::getMethods($foo);
        Reflection::getFunction('strtoupper');
        Reflection::getParametersForMethod($foo, '__construct');
        Reflection::getParametersForFunction('strtoupper');
        Reflection::getParametersForInvokable($bar);

        $cache = Reflection::getCache();

        self::assertNotEmpty($cache[Reflection::CACHE_CLASSES]);
        self::assertNotEmpty($cache[Reflection::CACHE_PROPERTIES]);
        self::assertNotEmpty($cache[Reflection::CACHE_METHODS]);
        self::assertNotEmpty($cache[Reflection::CACHE_FUNCTIONS]);
        self::assertNotEmpty($cache[Reflection::CACHE_PARAMETERS]);

        Reflection::clearCache();
        $cache = Reflection::getCache();

        self::assertEmpty($cache[Reflection::CACHE_CLASSES]);
        self::assertEmpty($cache[Reflection::CACHE_PROPERTIES]);
        self::assertEmpty($cache[Reflection::CACHE_METHODS]);
        self::assertEmpty($cache[Reflection::CACHE_FUNCTIONS]);
        self::assertEmpty($cache[Reflection::CACHE_PARAMETERS]);
    }
}
