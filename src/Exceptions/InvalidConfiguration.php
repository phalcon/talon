<?php

declare(strict_types=1);

namespace Phalcon\Talon\Exceptions;

class InvalidConfiguration extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct('Invalid configuration: ' . $reason);
    }
}
