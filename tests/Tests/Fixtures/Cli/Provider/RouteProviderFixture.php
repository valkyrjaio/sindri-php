<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Cli\Provider;

use Override;
use Valkyrja\Cli\Routing\Data\Route;
use Valkyrja\Cli\Routing\Provider\Contract\CliRouteProviderContract;

final class RouteProviderFixture implements CliRouteProviderContract
{
    #[Override]
    public static function getControllerClasses(): array
    {
        return ['AControllerClass'];
    }

    #[Override]
    public static function getRoutes(): array
    {
        return [
            new Route(
                name: 'test-provider',
                description: 'test',
                handler: static fn (): null => null,
            ),
        ];
    }
}
