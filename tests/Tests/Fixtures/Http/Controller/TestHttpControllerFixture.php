<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Http\Controller;

use Valkyrja\Http\Routing\Attribute\DynamicRoute;
use Valkyrja\Http\Routing\Attribute\Parameter;
use Valkyrja\Http\Routing\Attribute\Route;

final class TestHttpControllerFixture
{
    #[Route(path: '/test', name: 'test.route')]
    public function staticAction(): void
    {
    }

    #[DynamicRoute(path: '/test/{id}', name: 'test.dynamic')]
    #[Parameter(name: 'id', regex: '[0-9]+')]
    public function dynamicAction(): void
    {
    }
}
