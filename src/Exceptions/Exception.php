<?php

declare(strict_types=1);

namespace Phalcon\Talon\Exceptions;

use Exception as BaseException;
use Phalcon\Talon\Contracts\Throwable;

class Exception extends BaseException implements Throwable
{
}
