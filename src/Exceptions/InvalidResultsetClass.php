<?php

declare(strict_types=1);

namespace Phalcon\Talon\Exceptions;

class InvalidResultsetClass extends Exception
{
    public function __construct(string $class)
    {
        parent::__construct("'" . $class . "' is not a Phalcon\\Mvc\\Model\\Resultset subclass");
    }
}
