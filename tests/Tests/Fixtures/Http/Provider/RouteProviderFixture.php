<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Http\Provider;

use Override;
use Valkyrja\Container\Manager\Contract\ContainerContract;
use Valkyrja\Http\Message\Response\Contract\ResponseContract;
use Valkyrja\Http\Message\Response\Response;
use Valkyrja\Http\Routing\Data\Route;
use Valkyrja\Http\Routing\Provider\Contract\HttpRouteProviderContract;

final class RouteProviderFixture implements HttpRouteProviderContract
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
                path: '/from-provider',
                name: 'route-from-provider',
                handler: static fn (): null => null,
            ),
        ];
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    public static function handler(ContainerContract $container, array $arguments = []): ResponseContract
    {
        return new Response();
    }
}
