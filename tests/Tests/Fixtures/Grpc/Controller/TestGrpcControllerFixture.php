<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Grpc\Controller;

use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcMiddlewareFixture;
use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteMatchedOnlyMiddlewareFixture;
use Valkyrja\Container\Manager\Contract\ContainerContract;
use Valkyrja\Grpc\Message\Response\Contract\ServiceResponseContract;
use Valkyrja\Grpc\Message\Response\ServiceResponse;
use Valkyrja\Grpc\Routing\Attribute\Route;
use Valkyrja\Grpc\Routing\Attribute\Route\Middleware;
use Valkyrja\Grpc\Routing\Attribute\Service;
use Valkyrja\Grpc\Routing\Data\Contract\RouteContract;

#[Service(service: 'pkg.Greeter')]
final class TestGrpcControllerFixture
{
    #[Route(name: 'SayHello', handler: [self::class, 'sayHello'])]
    public static function sayHello(ContainerContract $container, RouteContract $route): ServiceResponseContract
    {
        return ServiceResponse::ok('hello');
    }

    #[Route(
        name: 'Chat',
        handler: [self::class, 'chat'],
        clientStreaming: true,
        serverStreaming: true
    )]
    #[Middleware(name: TestGrpcMiddlewareFixture::class)]
    public static function chat(ContainerContract $container, RouteContract $route): ServiceResponseContract
    {
        return ServiceResponse::ok('chat');
    }

    #[Route(name: 'Matched', handler: [self::class, 'matched'])]
    #[Middleware(name: TestGrpcRouteMatchedOnlyMiddlewareFixture::class)]
    public static function matched(ContainerContract $container, RouteContract $route): ServiceResponseContract
    {
        return ServiceResponse::ok('matched');
    }

    /**
     * Not attributed, so the scan must skip it.
     */
    public static function notAnRpc(ContainerContract $container, RouteContract $route): ServiceResponseContract
    {
        return ServiceResponse::ok('skipped');
    }
}
