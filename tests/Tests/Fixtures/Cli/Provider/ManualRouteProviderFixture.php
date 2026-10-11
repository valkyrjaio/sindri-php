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
use Valkyrja\Cli\Interaction\Output\Contract\OutputContract;
use Valkyrja\Cli\Interaction\Output\Output;
use Valkyrja\Cli\Routing\Data\Route;
use Valkyrja\Cli\Routing\Provider\Contract\CliRouteProviderContract;
use Valkyrja\Container\Manager\Contract\ContainerContract;

/**
 * A CLI route provider whose getRoutes() returns cacheable bare constructions.
 */
final class ManualRouteProviderFixture implements CliRouteProviderContract
{
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
                name: 'manual:named',
                description: 'Keyed by a named first argument',
                handler: [self::class, 'handler'],
            ),
            new Route('manual:positional', 'Keyed by a positional first argument', [self::class, 'handler']),
            new Route('test:cli-route', 'Takes the name of an attribute route', [self::class, 'handler']),
        ];
    }
}
