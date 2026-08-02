<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Cli\Middleware;

use LogicException;
use Throwable;
use Valkyrja\Cli\Interaction\Input\Contract\InputContract;
use Valkyrja\Cli\Interaction\Output\Contract\OutputContract;
use Valkyrja\Cli\Middleware\Contract\ProcessExitingMiddlewareContract;
use Valkyrja\Cli\Middleware\Contract\RouteDispatchedMiddlewareContract;
use Valkyrja\Cli\Middleware\Contract\RouteMatchedMiddlewareContract;
use Valkyrja\Cli\Middleware\Contract\ThrowableCaughtMiddlewareContract;
use Valkyrja\Cli\Middleware\Handler\Contract\ProcessExitingHandlerContract;
use Valkyrja\Cli\Middleware\Handler\Contract\RouteDispatchedHandlerContract;
use Valkyrja\Cli\Middleware\Handler\Contract\RouteMatchedHandlerContract;
use Valkyrja\Cli\Middleware\Handler\Contract\ThrowableCaughtHandlerContract;
use Valkyrja\Cli\Routing\Data\Contract\RouteContract;

final class TestCliMiddlewareFixture implements RouteMatchedMiddlewareContract, RouteDispatchedMiddlewareContract, ThrowableCaughtMiddlewareContract, ProcessExitingMiddlewareContract
{
    public function routeMatched(
        InputContract $input,
        RouteContract $route,
        RouteMatchedHandlerContract $handler,
    ): RouteContract|OutputContract {
        throw new LogicException('unreachable');
    }

    public function routeDispatched(
        InputContract $input,
        OutputContract $output,
        RouteContract $route,
        RouteDispatchedHandlerContract $handler,
    ): OutputContract {
        throw new LogicException('unreachable');
    }

    public function throwableCaught(
        InputContract $input,
        OutputContract $output,
        Throwable $throwable,
        ThrowableCaughtHandlerContract $handler,
    ): OutputContract {
        throw new LogicException('unreachable');
    }

    public function processExiting(
        InputContract $input,
        OutputContract $output,
        ProcessExitingHandlerContract $handler,
    ): void {
        throw new LogicException('unreachable');
    }
}
