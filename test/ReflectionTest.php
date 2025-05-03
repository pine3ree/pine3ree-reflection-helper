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
        ;
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

    public function testThatGetClassNameWorksForExistingClass()
    {
        $getClassName = new ReflectionMethod(Reflection::class, 'getClassName');
        $getClassName->setAccessible(true);

        self::assertEquals(Foo::class, $getClassName->invoke(null, Foo::class, true));
    }

    public function testThatGetClassNameReturnsNullForNonExistentClass()
    {
        $getClassName = new ReflectionMethod(Reflection::class, 'getClassName');
        $getClassName->setAccessible(true);

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

    public function testThatGetMethodReturnsReflectionMethodForExistingClassMethod()
    {
        $methodName = 'getTimestamp';

        $rm = Reflection::getMethod(DateTimeImmutable::class, $methodName);

        self::assertInstanceOf(ReflectionMethod::class, $rm);

    }

    public function testThatResolvedReflectionsMethodsAreCached()
    {
        $methodName = 'getTimestamp';

        $rm = Reflection::getMethod(DateTimeImmutable::class, $methodName);

        self::assertSame($rm, Reflection::getMethod(DateTimeImmutable::class, $methodName));
        self::assertSame($rm, Reflection::getMethod(new DateTimeImmutable(), $methodName));
        self::assertSame($rm, Reflection::getMethod(new DateTimeImmutable(), $methodName));
    }

    public function testConstructor()
    {
        $foo = new Foo('foo');

        $ctor = Reflection::getConstructor($foo);

        $methodName = '__construct';

        self::assertTrue($ctor->isConstructor());

        self::assertSame($ctor, Reflection::getConstructor(Foo::class));
        self::assertSame($ctor, Reflection::getMethod($foo, $methodName));
        self::assertSame($ctor, Reflection::getMethod(Foo::class, $methodName));
    }

    public function testThatGettingAnythingFromNonExistentClassReturnsNull()
    {
        self::assertNull(Reflection::getClass(NonExistentClass::class));
        self::assertNull(Reflection::getProperties(NonExistentClass::class));
        self::assertNull(Reflection::getProperty(NonExistentClass::class, 'someProperty'));
        self::assertNull(Reflection::getMethods(NonExistentClass::class));
        self::assertNull(Reflection::getMethod(NonExistentClass::class, 'someMethod'));
    }

    public function testThatGettingNonExistentPropertyReturnsNull()
    {
        self::assertNull(Reflection::getMethod(Foo::class, 'nonExistent'));
    }

    public function testThatGettingNonExistentMethodReturnsNull()
    {
        self::assertNull(Reflection::getProperty(Foo::class, 'nonExistent'));
    }

    public function testThatGettingNonExistentFunctionReturnsNull()
    {
        self::assertNull(Reflection::getFunction('nonExistentFunction'));
    }

    public function testThatExistingPropertyIsCached()
    {
        Reflection::clearCache();
        $cache = Reflection::getCache(Reflection::CACHE_PROPERTIES);
        self::assertIsArray($cache);
        self::assertEmpty($cache);

        $prop  = Reflection::getProperty(Foo::class, 'myName');

        $cache = Reflection::getCache(Reflection::CACHE_PROPERTIES);
        self::assertIsArray($cache);
        self::assertNotEmpty($cache);
        self::assertArrayHasKey(Foo::class, $cache);
        self::assertArrayHasKey('myName', $cache[Foo::class]);
        self::assertSame($prop, $cache[Foo::class]['myName']);

    }

    public function testThatAllPropertiesAreCached()
    {
        $props = Reflection::getProperties(Foo::class);
        $prop  = Reflection::getProperty(Foo::class, 'myName');

        $cache = Reflection::getCache(Reflection::CACHE_PROPERTIES);

        self::assertIsArray($cache);
        self::assertArrayHasKey(Foo::class, $cache);
        self::assertIsArray($cache[Foo::class]);
        self::assertArrayHasKey(Reflection::CACHE_ALL, $cache[Foo::class]);
        self::assertSame($props + [Reflection::CACHE_ALL => true], $cache[Foo::class]);

        self::assertArrayHasKey('myName', $cache[Foo::class]);
        self::assertSame($prop, $cache[Foo::class]['myName']);
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

    public function testThatFunctionsAreCached()
    {
        $rf1 = Reflection::getFunction('strtoupper');
        $rf2 = Reflection::getFunction('strtolower');

        $cache = Reflection::getCache(Reflection::CACHE_FUNCTIONS);

        self::assertArrayHasKey('strtoupper', $cache);
        self::assertArrayHasKey('strtolower', $cache);
        self::assertInstanceOf(ReflectionFunction::class, $cache['strtoupper']);
        self::assertInstanceOf(ReflectionFunction::class, $cache['strtolower']);
        self::assertSame($rf1, $cache['strtoupper']);
        self::assertSame($rf2, $cache['strtolower']);
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

    public function testThatEmptyCacheWorks()
    {
        Reflection::getProperties(Foo::class, true);
        $cache = Reflection::getCache();
        self::assertArrayHasKey(Reflection::CACHE_PROPERTIES, $cache);
        $cache = $cache[Reflection::CACHE_PROPERTIES];
        self::assertArrayHasKey(Foo::class, $cache);
    }

//    public function testDumpCache()
//    {
//        self::assertEquals(true, true);
//        echo "\n" . json_encode(Reflection::getCache(), JSON_PRETTY_PRINT) . "\n\n";
//    }
}
