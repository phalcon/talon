<?php

declare(strict_types=1);

namespace Phalcon\Talon\Exceptions;

class PhalconNotAvailable extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'Phalcon is not available: install the ext-phalcon extension or the phalcon/phalcon package'
        );
    }
}
