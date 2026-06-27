<?php

declare(strict_types=1);

namespace Phalcon\Talon\Exceptions;

class MissingService extends Exception
{
    public function __construct(string $service)
    {
        parent::__construct("The application DI has no '" . $service . "' service");
    }
}
