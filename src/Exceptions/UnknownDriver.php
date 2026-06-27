<?php

declare(strict_types=1);

namespace Phalcon\Talon\Exceptions;

class UnknownDriver extends Exception
{
    public function __construct(string $driver)
    {
        parent::__construct("Unknown database driver '" . $driver . "'");
    }
}
