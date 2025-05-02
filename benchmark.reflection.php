<?php

use pine3ree\Helper\Reflection;

require_once __DIR__ . "/vendor/autoload.php";

class MyArrayObject extends ArrayObject {

}

$iterations = 1 * 1000;

$ta = $ma = 0;
$tb = $mb = 0;
$tc = $mc = 0;

$rcs = [];
$rms = [];
$rps = [];

$rm = [];

$classes = [
    DateTime::class,
    MyArrayObject::Class,
    ArrayObject::class,
    PDO::class,
    mysqli::class,
    SplFixedArray::class,
    DateTimeImmutable::class,
//    SplObjectStorage::class,
];

$method = '__construct';

$m0 = memory_get_peak_usage(true);
$t0 = microtime(true);
for ($n = 0; $n < $iterations; $n += 1) {
    foreach ($classes as $class) {
        $rc = new ReflectionClass($class);
        $rms = $rc->getMethods();
        $rps = $rc->getProperties();
        $rm  = $rc->getMethod($method);
        $pms = $rm->getParameters();
    }
}
$t1 = microtime(true) - $t0;
$ta = $ta + $t1;
$m1 = memory_get_peak_usage(true) - $m0;
$ma = $ma + $m1;

$cache_results = false;
$m0 = memory_get_peak_usage(true);
$t0 = microtime(true);
//for ($n = 0; $n < $iterations; $n += 1) {
//    foreach ($classes as $class) {
//        $rms = Reflection::getMethods($class, $cache_results);
//        $rps = Reflection::getProperties($class, $cache_results);
//        $rm  = Reflection::getMethod($class, $method);
//        $pms = Reflection::getParametersForMethod($class, $method, true, $cache_results);
//    }
//}
$t1 = microtime(true) - $t0;
$tb = $tb + $t1;
$m1 = memory_get_peak_usage(true) - $m0;
$mb = $mb + $m1;

//$cache_results = true;
$m0 = memory_get_peak_usage(true);
$t0 = microtime(true);
for ($n = 0; $n < $iterations; $n += 1) {
    foreach ($classes as $class) {
        $rms = Reflection::getMethods($class);
        $rps = Reflection::getProperties($class);
        $rm  = Reflection::getMethod($class, $method);
        $pms = Reflection::getParametersForMethod($class, $method, true);
    }
}
$t1 = microtime(true) - $t0;
$tc = $tc + $t1;
$m1 = memory_get_peak_usage(true) - $m0;
$mc = $mc + $m1;

//echo "\n";
//echo "CACHED: {$n}\n";
//echo "\n";
//echo json_encode(Reflection::getCache(), JSON_PRETTY_PRINT) . "\n";
//echo "\n";

$tams = 1000 * $ta;
$tacs = 1000 * $tc;

echo "\n";
echo "ITERATIONS: {$n}\n";
echo "\n";
echo "t(PHP) = {$tams} ms\n";
//echo "t(P3N) = {$tb}\n";
echo "t(P3C) = {$tacs} ms\n";
echo "\n";
echo "MEMORY: {$n}\n";
echo "\n";
echo "m(PHP) = {$ma}\n";
//echo "m(P3N) = {$mb}\n";
echo "m(P3C) = {$mc}\n";

//print_r($flat);
//print_r($nest);
