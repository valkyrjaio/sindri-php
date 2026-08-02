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

use Valkyrja\Http\Routing\Attribute\Route;

class TestNoNsHttpControllerFixture
{
    #[Route(path: '/no-ns', name: 'no-ns.route')]
    public function action(): void
    {
    }
}
