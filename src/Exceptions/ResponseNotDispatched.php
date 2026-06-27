<?php

declare(strict_types=1);

namespace Phalcon\Talon\Exceptions;

class ResponseNotDispatched extends Exception
{
    public function __construct()
    {
        parent::__construct('Call dispatch() before asserting on the response');
    }
}
