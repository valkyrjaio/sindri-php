<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Grpc\Middleware;

use Valkyrja\Grpc\Message\Call\Contract\ServiceCallContract;
use Valkyrja\Grpc\Message\Response\Contract\ServiceResponseContract;
use Valkyrja\Grpc\Middleware\Contract\RouteDispatchedMiddlewareContract;
use Valkyrja\Grpc\Middleware\Handler\Contract\RouteDispatchedHandlerContract;
use Valkyrja\Grpc\Routing\Data\Contract\RouteContract;

/**
 * Serves one stage only, so a test can tell this stage's argument from the other four.
 */
final class TestGrpcRouteDispatchedOnlyMiddlewareFixture implements RouteDispatchedMiddlewareContract
{
    public function routeDispatched(ServiceCallContract $call, ServiceResponseContract $response, RouteContract $route, RouteDispatchedHandlerContract $handler): ServiceResponseContract
    {
        return $handler->routeDispatched($call, $response, $route);
    }
}
