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

return [
    'php'     => ['ext' => 123],
    'env'     => ['N' => 5],
    'suites'  => [
        0      => ['config' => 'zero.xml'],
        'unit' => ['config' => 'phpunit.xml'],
    ],
    'default' => 'unit',
];
