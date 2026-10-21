<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Queue\Provider;

use Override;
use Valkyrja\Container\Manager\Contract\ContainerContract;
use Valkyrja\Queue\Message\Enum\JobResult;
use Valkyrja\Queue\Routing\Data\Contract\RouteContract;
use Valkyrja\Queue\Routing\Data\Route;
use Valkyrja\Queue\Routing\Provider\Contract\QueueRouteProviderContract;

/**
 * A queue route provider whose getRoutes() returns shapes that the cache cannot hold.
 */
final class UnsupportedQueueRouteProviderFixture implements QueueRouteProviderContract
{
    public static function buildRoute(): Route
    {
        return new Route('manual:builder', 'Returned by a call, not constructed inline', [self::class, 'handler']);
    }

    public static function handler(ContainerContract $container, RouteContract $route): JobResult
    {
        return JobResult::ACK;
    }

    #[Override]
    public function getControllerClasses(): array
    {
        return [];
    }

    #[Override]
    public function getRoutes(): array
    {
        return [
            new (Route::class)('manual:expression-class', 'The construction names no class', [self::class, 'handler']),
            new Route(description: 'The name argument is absent', handler: [self::class, 'handler']),
            self::buildRoute(),
        ];
    }
}
