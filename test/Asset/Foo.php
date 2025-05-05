<?php

/**
 * @package    pine3ree-reflection
 * @subpackage pine3ree-reflection-test
 * @author     pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\test\Helper\Asset;

/**
 * Class Foo
 */
class Foo
{
    private string $myName;

    public function __construct(string $myName = 'foo')
    {
        $this->myName = $myName;
    }

    public function getName()
    {
        return $this->myName;
    }
}
