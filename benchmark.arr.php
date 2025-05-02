<?php

$k1s = [];
$k2s = [];
$k3s = [];
$k4s = [];

$flat = [];
$nest = [];

echo "\n";
echo "BUILDING ARRAYS\n";

$v = 0;

$n1 = 10;
$n2 = 100;
$n3 = 100;
$n4 = 10;

for ($n = 0; $n < $n1; $n += 1) {
    $k1s[] = 'k1' . bin2hex(random_bytes(3));
}
for ($n = 0; $n < $n2; $n += 1) {
    $k2s[] = 'k2' . bin2hex(random_bytes(3));
}
for ($n = 0; $n < $n3; $n += 1) {
    $k3s[] = 'k3' . bin2hex(random_bytes(3));
}
for ($n = 0; $n < $n4; $n += 1) {
    $k4s[] = 'k4' . bin2hex(random_bytes(3));
}

foreach ($k1s as $k1) {
    foreach ($k2s as $k2) {
        foreach ($k3s as $k3) {
            foreach ($k4s as $k4) {
                $k = "{$k1}::{$k2}::{$k3}::{$k4}";

                $v = $v + 1;

                $flat[$k] = $v;
                $nest[$k1][$k2][$k3][$k4] = $v;
            }
        }
    }
}

//
//for ($n = 0; $n < $n1; $n += 1) {
//    $k1 = 'k1' . bin2hex(random_bytes(3));
//    $k1s[$k1] = $k1;
//    for ($m = 0; $m < $n2; $m += 1) {
//        $k2 = 'k2' . bin2hex(random_bytes(3));
//        $k2s[$k2] = $k2;
//        for ($p = 0; $p < $n3; $p += 1) {
//            $k3 = 'k3' . bin2hex(random_bytes(3));
//            $k3s[$k3] = $k3;
//            for ($q = 0; $q < $n4; $q += 1) {
//                $k4 = 'k4' . bin2hex(random_bytes(3));
//                $k4s[$k4] = $k4;
//
//                $k = "{$k1}::{$k2}::{$k3}::{$k4}";
//                $v = $v + 1;
////                $v = $n + $m + $p + $q;
//
//                $flat[$k] = $v;
//
//                $nest[$k1][$k2][$k3][$k4] = $v;
//            }
//        }
//    }
//}

echo "DONE\n";

$tf = 0;
$tn = 0;

for ($n = 0; $n < 1000000; $n += 1) {
    $k1 = $k1s[array_rand($k1s)];
    $k2 = $k2s[array_rand($k2s)];
    $k3 = $k3s[array_rand($k3s)];
    $k4 = $k4s[array_rand($k4s)];

    $k = "{$k1}::{$k2}::{$k3}::{$k4}";

//    echo "{$k}\n";

    $t0 = microtime(true);
    $vf = $flat[$k] ?? '?';
    $t1 = microtime(true) - $t0;
    $tf = $tf + $t1;

//    echo "vf = {$vf}\n";

    $t0 = microtime(true);
    $vn = $nest[$k1][$k2][$k3][$k4] ?? '?';
    $t1 = microtime(true) - $t0;
    $tn = $tn + $t1;

//    echo "vn = {$vn}\n";

    if ($vf !== $vn) {
        echo "{$vf} !== {$vn}\n";
    }
}

echo "\n";
echo "ITERATIONS: {$n}\n";
echo "\n";
echo "t(flat) = {$tf}\n";
echo "t(nest) = {$tn}\n";

//print_r($flat);
//print_r($nest);
