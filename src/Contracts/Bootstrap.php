<?php

declare(strict_types=1);

namespace Phalcon\Talon\Contracts;

interface Bootstrap
{
    public function boot(): Settings;
}
