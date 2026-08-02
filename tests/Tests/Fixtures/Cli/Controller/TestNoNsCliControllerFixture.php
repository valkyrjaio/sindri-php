<?php

// phpcs:ignoreFile

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

use Valkyrja\Cli\Routing\Attribute\Route;

// Intentionally no namespace — covers the $namespace === '' branch in readFile
class TestNoNsCliControllerFixture
{
    #[Route(name: 'no-ns:cli-route', description: 'A CLI route in a no-namespace class')]
    public function testAction(): void
    {
    }
}
