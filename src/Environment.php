<?php

/**
 * This file is part of the Phalcon Talon.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Talon;

use Phalcon\Di\FactoryDefault;

use function class_exists;
use function extension_loaded;

final class Environment
{
    public static function phalconAvailable(): bool
    {
        return self::viaExtension() || self::viaImplementation();
    }

    public static function viaExtension(): bool
    {
        return extension_loaded('phalcon');
    }

    public static function viaImplementation(): bool
    {
        // The PHP implementation provides the same FQCN without the extension.
        return !extension_loaded('phalcon')
            && class_exists(FactoryDefault::class);
    }
}
