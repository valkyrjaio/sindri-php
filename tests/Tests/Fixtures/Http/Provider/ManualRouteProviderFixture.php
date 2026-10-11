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
use Valkyrja\Http\Message\Enum\RequestMethod;
use Valkyrja\Http\Message\Response\Contract\ResponseContract;
use Valkyrja\Http\Message\Response\Response;
use Valkyrja\Http\Routing\Data\Route;
use Valkyrja\Http\Routing\Provider\Contract\HttpRouteProviderContract;

/**
 * An HTTP route provider whose getRoutes() returns cacheable bare constructions.
 */
final class ManualRouteProviderFixture implements HttpRouteProviderContract
{
    /**
     * @param array<array-key, mixed> $arguments
     */
    public static function handler(ContainerContract $container, array $arguments = []): ResponseContract
    {
        return new Response();
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
            new Route(
                path: '/manual-default',
                name: 'manual.default',
                handler: [self::class, 'handler'],
            ),
            new Route('/manual-put', 'manual.put', [self::class, 'handler'], [RequestMethod::PUT]),
            new Route('/manual-any', 'manual.any', [self::class, 'handler'], [RequestMethod::ANY]),
            new Route('/manual-none', 'manual.none', [self::class, 'handler'], []),
        ];
    }
}
