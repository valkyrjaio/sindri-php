<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Http\Middleware;

use LogicException;
use Throwable;
use Valkyrja\Http\Message\Request\Contract\ServerRequestContract;
use Valkyrja\Http\Message\Response\Contract\ResponseContract;
use Valkyrja\Http\Middleware\Contract\ResponseSentMiddlewareContract;
use Valkyrja\Http\Middleware\Contract\RouteDispatchedMiddlewareContract;
use Valkyrja\Http\Middleware\Contract\RouteMatchedMiddlewareContract;
use Valkyrja\Http\Middleware\Contract\SendingResponseMiddlewareContract;
use Valkyrja\Http\Middleware\Contract\ThrowableCaughtMiddlewareContract;
use Valkyrja\Http\Middleware\Handler\Contract\ResponseSentHandlerContract;
use Valkyrja\Http\Middleware\Handler\Contract\RouteDispatchedHandlerContract;
use Valkyrja\Http\Middleware\Handler\Contract\RouteMatchedHandlerContract;
use Valkyrja\Http\Middleware\Handler\Contract\SendingResponseHandlerContract;
use Valkyrja\Http\Middleware\Handler\Contract\ThrowableCaughtHandlerContract;
use Valkyrja\Http\Routing\Data\Contract\RouteContract;

final class TestHttpMiddlewareFixture implements RouteMatchedMiddlewareContract, RouteDispatchedMiddlewareContract, ThrowableCaughtMiddlewareContract, SendingResponseMiddlewareContract, ResponseSentMiddlewareContract
{
    public function routeMatched(
        ServerRequestContract $request,
        RouteContract $route,
        RouteMatchedHandlerContract $handler,
    ): RouteContract|ResponseContract {
        throw new LogicException('unreachable');
    }

    public function routeDispatched(
        ServerRequestContract $request,
        ResponseContract $response,
        RouteContract $route,
        RouteDispatchedHandlerContract $handler,
    ): ResponseContract {
        throw new LogicException('unreachable');
    }

    public function throwableCaught(
        ServerRequestContract $request,
        ResponseContract $response,
        Throwable $throwable,
        ThrowableCaughtHandlerContract $handler,
    ): ResponseContract {
        throw new LogicException('unreachable');
    }

    public function sendingResponse(
        ServerRequestContract $request,
        ResponseContract $response,
        SendingResponseHandlerContract $handler,
    ): ResponseContract {
        throw new LogicException('unreachable');
    }

    public function responseSent(
        ServerRequestContract $request,
        ResponseContract $response,
        ResponseSentHandlerContract $handler,
    ): void {
        throw new LogicException('unreachable');
    }
}
