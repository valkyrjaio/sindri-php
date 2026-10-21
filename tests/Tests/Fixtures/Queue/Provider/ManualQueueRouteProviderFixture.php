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
use Sindri\Tests\Fixtures\Queue\Controller\TestQueueControllerFixture;
use Valkyrja\Container\Manager\Contract\ContainerContract;
use Valkyrja\Queue\Message\Enum\JobResult;
use Valkyrja\Queue\Routing\Data\Contract\RouteContract;
use Valkyrja\Queue\Routing\Data\Route;
use Valkyrja\Queue\Routing\Provider\Contract\QueueRouteProviderContract;

/**
 * A queue route provider whose getRoutes() returns cacheable bare constructions.
 */
final class ManualQueueRouteProviderFixture implements QueueRouteProviderContract
{
    public static function handler(ContainerContract $container, RouteContract $route): JobResult
    {
        return JobResult::ACK;
    }

    #[Override]
    public function getControllerClasses(): array
    {
        return [
            TestQueueControllerFixture::class,
        ];
    }

    #[Override]
    public function getRoutes(): array
    {
        return [
            new Route(
                name: 'manual:named',
                description: 'Keyed by a named first argument',
                handler: [self::class, 'handler'],
            ),
            new Route('manual:positional', 'Keyed by a positional first argument', [self::class, 'handler']),
        ];
    }
}
