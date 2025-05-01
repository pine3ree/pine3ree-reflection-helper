<?php

/**
 * @package    pine3ree-reflection
 * @subpackage pine3ree-reflection-test
 * @author     pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Helper\Asset;

/**
 * Class Foo
 */
class Foo
{
    private string $name;

    public function __construct(string $name = 'foo')
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
