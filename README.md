# pine3ree-reflection-helper

[![Continuous Integration](https://github.com/pine3ree/pine3ree-reflection-helper/actions/workflows/continuos-integration.yml/badge.svg)](https://github.com/pine3ree/pine3ree-reflection-helper/actions/workflows/continuos-integration.yml)

This package provides a simple helper that return reflection classes for given
object/class/function and cache the results.

```php
<?php

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

use pine3ree\Helper\Reflection;

Reflection::getClass(object|class-string $objectOrClass); // @return ReflectionClass|null
Reflection::getProperties(object|class-string $objectOrClass); // @return array<string|ReflectionProperty>|null
Reflection::getProperty(object|class-string $objectOrClass, string $name); // @return ReflectionProperty|null
Reflection::getMethods(object|class-string $objectOrClass); // @return array<string|ReflectionMethod>|null
Reflection::getMethod(object|class-string $objectOrClass, string $name); // @return ReflectionMethod|null
Reflection::getConstructor(objectOrClass); // @return ReflectionMethod|null
Reflection::getFunction(string $function); // @return ReflectionFunction|null
Reflection::getParametersForMethod(object|class-string $objectOrClass, string $name, bool $check_existence = true); // @return array<int, ReflectionParameter>|null
Reflection::getParametersForConstructor(object|class-string $objectOrClass, bool  $check_existence = true); // @return ReflectionParameter[]|null
Reflection::getParametersForInvokable(object $invokable); // @return ReflectionParameter[]|null
Reflection::getParametersForFunction(string $function, bool $check_existence = true); // @return ReflectionParameter[]|null
Reflection::clearCache(string $type = self::CACHE_ALL): void

```
