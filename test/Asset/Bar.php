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
    public function __invoke()
    {
        return $this->getFullName();
    }

    public function getFullName()
    {
        return "{$this->getName()}bar";
    }
}
