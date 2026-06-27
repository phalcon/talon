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

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/../src', __DIR__ . '/../tests']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12'               => true,
        'declare_strict_types' => true,
        'ordered_imports'      => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'    => true,
        'array_syntax'         => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
