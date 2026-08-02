<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Provider;

use Sindri\Cli\Command\GenerateDataFromConfigCommand;
use Sindri\Provider\SindriCliRouteProvider;
use Sindri\Tests\Unit\Abstract\TestCase;
use Valkyrja\Cli\Interaction\Output\Contract\OutputContract;
use Valkyrja\Cli\Routing\Data\Contract\RouteContract;
use Valkyrja\Container\Manager\Contract\ContainerContract;

final class SindriCliRouteProviderTest extends TestCase
{
    public function testGetControllerClassesReturnsGenerateDataFromConfigCommand(): void
    {
        $classes = new SindriCliRouteProvider()->getControllerClasses();

        self::assertSame([GenerateDataFromConfigCommand::class], $classes);
    }

    public function testGetRoutesReturnsEmptyArray(): void
    {
        $routes = new SindriCliRouteProvider()->getRoutes();

        self::assertSame([], $routes);
    }

    public function testCliGenerateDataHandlerDelegatesToCommand(): void
    {
        $output  = self::createStub(OutputContract::class);
        $command = $this->createMock(GenerateDataFromConfigCommand::class);
        $command->expects($this->once())->method('run')->willReturn($output);

        $container = self::createStub(ContainerContract::class);
        $container->method('getSingleton')->willReturn($command);

        $result = SindriCliRouteProvider::cliGenerateDataHandler($container, self::createStub(RouteContract::class));

        self::assertSame($output, $result);
    }
}
