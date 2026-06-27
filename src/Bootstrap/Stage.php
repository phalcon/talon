<?php

declare(strict_types=1);

namespace Phalcon\Talon\Bootstrap;

enum Stage
{
    case Directories;
    case Environment;
    case Settings;
}
