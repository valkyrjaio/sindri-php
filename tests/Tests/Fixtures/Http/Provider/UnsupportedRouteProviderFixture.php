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
use Sindri\Tests\Fixtures\TestRouteConstantsFixture;
use Valkyrja\Container\Manager\Contract\ContainerContract;
use Valkyrja\Http\Message\Enum\RequestMethod;
use Valkyrja\Http\Message\Response\Contract\ResponseContract;
use Valkyrja\Http\Message\Response\Response;
use Valkyrja\Http\Routing\Data\DynamicRoute;
use Valkyrja\Http\Routing\Data\Parameter;
use Valkyrja\Http\Routing\Data\Route;
use Valkyrja\Http\Routing\Provider\Contract\HttpRouteProviderContract;

/**
 * An HTTP route provider whose getRoutes() returns shapes that the cache cannot hold.
 */
final class UnsupportedRouteProviderFixture implements HttpRouteProviderContract
{
    private const array ROUTE_MIDDLEWARE = [];

    /** @var class-string[] */
    private array $routeMiddleware = [];

    /**
     * @param array<array-key, mixed> $arguments
     */
    public static function handler(ContainerContract $container, array $arguments = []): ResponseContract
    {
        return new Response();
    }

    /**
     * @return class-string[]
     */
    private static function routeMiddleware(): array
    {
        return [];
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
            new DynamicRoute(
                '/manual-dynamic/{id}',
                'manual.dynamic',
                '/^\/manual-dynamic\/(?<id>[0-9]+)$/',
                [new Parameter(name: 'id', regex: '[0-9]+')],
                [self::class, 'handler'],
            ),
            new Route(TestRouteConstantsFixture::HTTP_PATH, 'manual.non-literal-path', [self::class, 'handler']),
            new Route('/manual-non-literal-name', TestRouteConstantsFixture::HTTP_NAME, [self::class, 'handler']),
            new (Route::class)('/manual-expression-class', 'manual.expression-class', [self::class, 'handler']),
            new Route(
                '/manual-unreadable-methods',
                'manual.unreadable-methods',
                [self::class, 'handler'],
                [RequestMethod::PUT, ...TestRouteConstantsFixture::REQUEST_METHODS],
            ),
            new Route(
                '/manual-non-literal-method-list',
                'manual.non-literal-method-list',
                [self::class, 'handler'],
                TestRouteConstantsFixture::REQUEST_METHODS,
            ),
            new Route('/manual-chained', 'manual.chained', [self::class, 'handler'])
                ->withAddedRequestMethods(RequestMethod::POST),
            new Route('/manual-spread', 'manual.spread', [self::class, 'handler'], ...TestRouteConstantsFixture::SPREAD_ARGUMENTS),
            new Route('/manual-method-class', 'manual.method-class', [self::class, 'handler'], [RequestMethod::class]),
            new Route('/manual-string-method', 'manual.string-method', [self::class, 'handler'], ['GET']),
            new Route(
                '/manual-expression-method',
                'manual.expression-method',
                [self::class, 'handler'],
                [(new TestRouteConstantsFixture())::HTTP_NAME],
            ),
            new Route(
                '/manual-closure-handler',
                'manual.closure-handler',
                static fn (): ResponseContract => new Response(),
            ),
            new Route(
                '/manual-property',
                'manual.property',
                [self::class, 'handler'],
                [RequestMethod::GET],
                $this->routeMiddleware,
            ),
            new Route(
                '/manual-call',
                'manual.call',
                [self::class, 'handler'],
                [RequestMethod::GET],
                self::routeMiddleware(),
            ),
            new Route(
                '/manual-own-constant',
                'manual.own-constant',
                [self::class, 'handler'],
                [RequestMethod::GET],
                self::ROUTE_MIDDLEWARE,
            ),
        ];
    }
}
