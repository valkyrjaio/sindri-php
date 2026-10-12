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
use Sindri\Tests\Fixtures\TestRouteConstantsFixture;
use Valkyrja\Cli\Interaction\Output\Contract\OutputContract;
use Valkyrja\Cli\Interaction\Output\Output;
use Valkyrja\Cli\Routing\Data\Route;
use Valkyrja\Cli\Routing\Provider\Contract\CliRouteProviderContract;
use Valkyrja\Container\Manager\Contract\ContainerContract;

/**
 * A CLI route provider whose getRoutes() returns shapes that the cache cannot hold.
 */
final class UnsupportedRouteProviderFixture implements CliRouteProviderContract
{
    public static function buildRoute(): Route
    {
        return new Route('manual:builder', 'Returned by a call, not constructed inline', [self::class, 'handler']);
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    public static function handler(ContainerContract $container, array $arguments = []): OutputContract
    {
        return new Output();
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
                TestRouteConstantsFixture::CLI_NAME,
                'The name is not a string literal',
                [self::class, 'handler'],
            ),
            new (Route::class)('manual:expression-class', 'The construction names no class', [self::class, 'handler']),
            new Route(description: 'The name argument is absent', handler: [self::class, 'handler']),
            new Route('manual:chained', 'Declared by a builder chain', [self::class, 'handler'])
                ->withName('manual:renamed'),
            self::buildRoute(),
        ];
    }
}
