<?php

declare(strict_types=1);

namespace Phalcon\Talon\Exceptions;

class InvalidApplication extends Exception
{
    public function __construct(string $givenType)
    {
        parent::__construct(
            "appFactory() must return an object with handle(); got '" . $givenType . "'"
        );
    }
}
