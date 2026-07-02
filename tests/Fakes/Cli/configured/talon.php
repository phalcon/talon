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
    'php'     => ['extension=fake.so'],
    'env'     => ['GLOBAL_ENV' => 'yes', 'SHARED' => 'global'],
    'suites'  => [
        'unit' => [
            'config' => 'custom/unit.xml',
            'args'   => ['--testdox'],
        ],
        'db'   => [
            'config' => 'custom/db.xml',
            'php'    => ['memory_limit=1G'],
            'env'    => ['SHARED' => 'suite', 'DB_ONLY' => '1'],
        ],
    ],
    'default' => 'db',
];
