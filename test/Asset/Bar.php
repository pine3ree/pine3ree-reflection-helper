<?php

/**
 * @package    pine3ree-reflection
 * @subpackage pine3ree-reflection-test
 * @author     pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Helper\Asset;

/**
 * Class Bar
 */
class Bar extends Foo
{
    public function __invoke(bool $full = true)
    {
        return $full ? $this->getFullName() :  $this->getName();
    }

    public function getFullName()
    {
        return "{$this->getName()}bar";
    }
}
