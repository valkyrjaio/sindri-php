<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

use PhpCsFixer\Finder;
use Valkyrja\Fixer\Rules;

$finder = Finder::create()
    // Finder ignores a dot directory by default, which put every PHP file under
    // .github outside the header rule. Those files are this repository's own source
    // and carry the header too, so the finder descends into them.
    ->ignoreDotFiles(false)
    // The finder matches *.php, which left the extensionless bin entry point outside every
    // rule, including the header rule. Its header went unchecked for that reason. The name
    // is added so the entry point gets the same treatment as every other source file.
    ->name('sindri')
    ->exclude('.git')
    ->exclude('vendor')
    ->in(__DIR__ . '/../../../');

return Rules::getConfig($finder, 'Sindri');
