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

//    public function testBase()
//    {
//        $date = new DateTimeImmutable();
//
//        $rc = new ReflectionClass($date);
//        $rm = $rc->getConstructor();
//
//        self::assertSame($rc->getName(), $rm->getDeclaringClass()->getName());
//
//////        var_dump($rm->getDeclaringClass()->getName());
////        var_dump($rm->getDeclaringClass());
////        var_dump($rm->getName());
////        var_dump($rm->getNamespaceName());
//
//    }

    public function testThatGetClassRetunsNullForEmptyClass()
    {
        self::assertNull(Reflection::getClass(''));
    }

    public function testThatGetClassNameRetunsNullForNonExistentClass()
    {
        $getClassName = new ReflectionMethod(Reflection::class, 'getClassName');
        $getClassName->setAccessible(true);
        self::assertNull($getClassName->invoke(null, NonExistentClass::class, true));
    }

    public function testThatGetClassNameRetunsNullForInvalidObjectOrClass()
    {
        $getClassName = new ReflectionMethod(Reflection::class, 'getClassName');
        $getClassName->setAccessible(true);
        self::assertNull($getClassName->invoke(null, 123, true));
    }

//    public function testConstructor()
//    {
//        $date = new DateTimeImmutable();
//
//        $ctor = Reflection::getConstructor($date);
//
//        $methodName = '__construct';
//
//        self::assertSame($ctor, Reflection::getConstructor(new DateTimeImmutable()));
//        self::assertSame($ctor, Reflection::getConstructor(DateTimeImmutable::class));
//        self::assertSame($ctor, Reflection::getMethod(DateTimeImmutable::class, $methodName));
//        self::assertSame($ctor, Reflection::getMethod(DateTimeImmutable::class, $methodName));
//        self::assertSame($ctor, Reflection::getMethod(new DateTimeImmutable(), $methodName));
//        self::assertSame($ctor, Reflection::getMethod(new DateTimeImmutable(), $methodName));
//
//        $rm = Reflection::getMethod(Reflection::class, 'getMethod');
//
////        var_dump($rm->getDeclaringClass());
////        var_dump($rm->getName());
////        var_dump($rm->getNamespaceName());
//    }

//    public function testMethods()
//    {
//        $methodName = 'getTimestamp';
//
//        $rm = Reflection::getMethod(DateTimeImmutable::class, $methodName);
//
//        self::assertSame($rm, Reflection::getMethod(new DateTimeImmutable(), $methodName));
//        self::assertSame($rm, Reflection::getMethod(new DateTimeImmutable(), $methodName));
//        self::assertSame($rm, Reflection::getMethod(DateTimeImmutable::class, $methodName));
//        self::assertSame($rm, Reflection::getMethod(DateTimeImmutable::class, $methodName));
//    }

//    public function testProperties()
//    {
//        $propertyName = 'date';
//
//        $rp = Reflection::getProperty(DateTime::class, $propertyName);
//
//        self::assertSame($rp, Reflection::getProperty(new DateTime(), $propertyName));
//        self::assertSame($rp, Reflection::getProperty(new DateTime(), $propertyName));
//        self::assertSame($rp, Reflection::getProperty(DateTime::class, $propertyName));
//        self::assertSame($rp, Reflection::getProperty(DateTime::class, $propertyName));
//    }

    public function testCachedIsAddedForMethodDeclaringClass()
    {
        $bar = new Bar();

        Reflection::getClass($bar);
        Reflection::getProperties($bar);
        Reflection::getConstructor($bar);
        Reflection::getMethod($bar, 'nonExistent');
        Reflection::getMethods($bar);
        $bar_constructor = Reflection::getConstructor($bar);
//        $bar__construct = Reflection::getMethod($bar, '__construct');
        $bar_getName = Reflection::getMethod($bar, 'getName');
        Reflection::getParameters($bar, 'getName', true);
//        $foo_getName = Reflection::getMethod(Foo::class, 'getName');
        Reflection::getMethod($bar, 'getFullName');
//        Reflection::getMethod(Bar::class, 'getFullName');

        Reflection::getMethods($bar);
        Reflection::getMethods(NonExistentClass::class);
        Reflection::getMethod(NonExistentClass::class, 'someMethod');

//        echo "\n" . json_encode($rms, JSON_PRETTY_PRINT) . "\n\n";

        self::assertSame(true, true);
    }

    public function testThatGettingAnythingFromNonExistentClassReturnsNull()
    {
        self::assertNull(Reflection::getClass(NonExistentClass::class));
        self::assertNull(Reflection::getProperties(NonExistentClass::class));
        self::assertNull(Reflection::getProperty(NonExistentClass::class, 'someProperty'));
        self::assertNull(Reflection::getMethods(NonExistentClass::class, 'someMethod'));
    }

    public function testThatGettingAnythingNonExistentFromExistingClassReturnsNull()
    {
        self::assertNull(Reflection::getProperty(Foo::class, 'nonExistentProperty'));
        self::assertNull(Reflection::getMethod(Foo::class, 'nonExistentMethod'));
    }

    public function testThatGettingNonExistentPropertyReturnsNull()
    {
        self::assertNull(Reflection::getMethod(Bar::class, 'nonExistent'));
    }

    public function testThatGettingNonExistentMethodReturnsNull()
    {
        self::assertNull(Reflection::getProperty(Bar::class, 'nonExistent'));
    }

    public function testClosure()
    {
        $func = function () {
            return 42;
        };

        $func2 = function () {
            return 27;
        };

        $rm = Reflection::getMethod($func, '__invoke');
        self::assertInstanceOf(ReflectionMethod::class, $rm);
//        var_dump($rm->getDeclaringClass());

        $rm = Reflection::getMethod($func2, '__invoke');
        self::assertInstanceOf(ReflectionMethod::class, $rm);
//        var_dump($rm->getDeclaringClass());

        $rf = new ReflectionFunction($func);
        self::assertInstanceOf(ReflectionFunction::class, $rf);

        $rf2 = new ReflectionFunction($func2);
        self::assertInstanceOf(ReflectionFunction::class, $rf2);

//        var_dump($rf2->getName());
    }

    public function testEmptyCache()
    {
        Reflection::getProperties(Foo::class, true);
        $cache = Reflection::getCache();
        self::assertArrayHasKey(Reflection::CACHE_PROPERTIES, $cache);
        $cache = $cache[Reflection::CACHE_PROPERTIES];
        self::assertArrayHasKey(Foo::class, $cache);
    }

    public function testDumpCache()
    {
        self::assertEquals(true, true);

//        $obj = new class {
//            public function __invoke(string $name, array $arguments)
//            {
//                ;
//            }
//        };
//        self::assertTrue(is_callable($obj));

//
//        $rm = new \ReflectionMethod($func, '__invoke');
//        $rf = new \ReflectionFunction($func);
//
//        self::assertEquals($func(), $rm->invoke($func));
//        self::assertEquals($func(), $rf->invoke());

////        $rc = Reflection::getClass(Reflection::class);
////        var_dump($rc->getProperties());
//        Reflection::getProperties(Reflection::class);
//        Reflection::getProperty(Reflection::class, 'cache');
//        Reflection::getMethods(Reflection::class);
//
//        Reflection::getProperties(DateTimeImmutable::class);
//        Reflection::getMethods(DateTimeImmutable::class);
//
//        Reflection::getFunction('date');
//        Reflection::getFunction('htmlspecialchars');

//        echo "\n" . json_encode(Reflection::getCache(), JSON_PRETTY_PRINT) . "\n\n";
//        echo "\n" . json_encode(Reflection::getProperties(Reflection::class), JSON_PRETTY_PRINT) . "\n\n";

    }

    public function callReflectionClassName($objectOrClass)
    {
        static $getClassName = null;

        if ($getClassName === null) {
            $getClassName = new ReflectionMethod(Reflection::class, 'getClassName');
            $getClassName->setAccessible(true);
        }

        return $getClassName->invoke(null, $objectOrClass);
    }
}
