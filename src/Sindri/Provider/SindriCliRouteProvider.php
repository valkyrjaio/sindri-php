<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Provider;

use Override;
use Sindri\Cli\Command\GenerateDataFromConfigCommand;
use Valkyrja\Cli\Interaction\Output\Contract\OutputContract;
use Valkyrja\Cli\Routing\Data\Contract\RouteContract;
use Valkyrja\Cli\Routing\Provider\Contract\CliRouteProviderContract;
use Valkyrja\Container\Manager\Contract\ContainerContract;

class SindriCliRouteProvider implements CliRouteProviderContract
{
    /**
     * Handler for the CliGenerateDataFromAstCommand command.
     */
    public static function cliGenerateDataHandler(ContainerContract $container, RouteContract $route): OutputContract
    {
        return $container->getSingleton(GenerateDataFromConfigCommand::class)->run();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getControllerClasses(): array
    {
        return [
            GenerateDataFromConfigCommand::class,
        ];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getRoutes(): array
    {
        return [];
    }
}
