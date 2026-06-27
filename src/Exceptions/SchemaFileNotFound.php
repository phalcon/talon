<?php

declare(strict_types=1);

namespace Phalcon\Talon\Exceptions;

class SchemaFileNotFound extends Exception
{
    public function __construct(string $path)
    {
        parent::__construct("Schema file not found: '" . $path . "'");
    }
}
