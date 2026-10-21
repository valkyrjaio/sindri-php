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

use ReflectionProperty;
use Sindri\Ast\CliRouteAttributeReader;
use Sindri\Ast\CliRouteParameterReader;
use Sindri\Ast\ComponentProviderReader;
use Sindri\Ast\ConfigReader;
use Sindri\Ast\Contract\CliRouteAttributeReaderContract;
use Sindri\Ast\Contract\ComponentProviderReaderContract;
use Sindri\Ast\Contract\ConfigReaderContract;
use Sindri\Ast\Contract\GrpcRouteAttributeReaderContract;
use Sindri\Ast\Contract\HttpRouteAttributeReaderContract;
use Sindri\Ast\Contract\ListenerAttributeReaderContract;
use Sindri\Ast\Contract\ListenerProviderReaderContract;
use Sindri\Ast\Contract\RouteProviderReaderContract;
use Sindri\Ast\Contract\ServiceProviderReaderContract;
use Sindri\Ast\GrpcRouteAttributeReader;
use Sindri\Ast\HttpRouteAttributeReader;
use Sindri\Ast\HttpRouteMiddlewareReader;
use Sindri\Ast\HttpRouteParameterReader;
use Sindri\Ast\ListenerAttributeReader;
use Sindri\Ast\ListenerProviderReader;
use Sindri\Ast\RouteProviderReader;
use Sindri\Ast\ServiceProviderReader;
use Sindri\Cli\Command\GenerateDataFromConfigCommand;
use Sindri\Generator\Ast\Cli\AstCliDataFileGenerator;
use Sindri\Generator\Ast\Container\AstContainerDataFileGenerator;
use Sindri\Generator\Ast\Event\AstEventDataFileGenerator;
use Sindri\Generator\Ast\Grpc\AstGrpcDataFileGenerator;
use Sindri\Generator\Ast\Http\AstHttpDataFileGenerator;
use Sindri\Generator\Cli\Contract\CliDataFileGeneratorContract;
use Sindri\Generator\Container\Contract\ContainerDataFileGeneratorContract;
use Sindri\Generator\Event\Contract\EventDataFileGeneratorContract;
use Sindri\Generator\Grpc\Contract\GrpcDataFileGeneratorContract;
use Sindri\Generator\Http\Contract\HttpDataFileGeneratorContract;
use Sindri\Provider\SindriCommandServiceProvider;
use Valkyrja\Cli\Interaction\Output\Factory\Contract\OutputFactoryContract;
use Valkyrja\Cli\Routing\Data\Contract\RouteContract;
use Valkyrja\PhpUnit\Abstract\ServiceProviderTestCase;

final class SindriCommandServiceProviderTest extends ServiceProviderTestCase
{
    /** @inheritDoc */
    protected static string $provider = SindriCommandServiceProvider::class;

    public function testExpectedPublishers(): void
    {
        self::assertArrayHasKey(GenerateDataFromConfigCommand::class, new SindriCommandServiceProvider()->publishers());
    }

    public function testPublishGenerateDataFromConfigCommand(): void
    {
        $container = $this->container;
        $container->setSingleton(RouteContract::class, self::createStub(RouteContract::class));
        $container->setSingleton(OutputFactoryContract::class, self::createStub(OutputFactoryContract::class));
        $container->setSingleton(ConfigReaderContract::class, new ConfigReader());
        $container->setSingleton(ComponentProviderReaderContract::class, new ComponentProviderReader());
        $container->setSingleton(RouteProviderReaderContract::class, new RouteProviderReader());
        $container->setSingleton(ListenerProviderReaderContract::class, new ListenerProviderReader());
        $container->setSingleton(ServiceProviderReaderContract::class, new ServiceProviderReader());
        $container->setSingleton(CliRouteAttributeReaderContract::class, new CliRouteAttributeReader(parameterReader: new CliRouteParameterReader()));
        $container->setSingleton(HttpRouteAttributeReaderContract::class, new HttpRouteAttributeReader(parameterReader: new HttpRouteParameterReader(), middlewareReader: new HttpRouteMiddlewareReader()));
        $container->setSingleton(GrpcRouteAttributeReaderContract::class, new GrpcRouteAttributeReader());
        $container->setSingleton(ListenerAttributeReaderContract::class, new ListenerAttributeReader());
        $container->setSingleton(ContainerDataFileGeneratorContract::class, new AstContainerDataFileGenerator());
        $container->setSingleton(EventDataFileGeneratorContract::class, new AstEventDataFileGenerator());
        $container->setSingleton(CliDataFileGeneratorContract::class, new AstCliDataFileGenerator());
        $container->setSingleton(HttpDataFileGeneratorContract::class, new AstHttpDataFileGenerator());
        $container->setSingleton(GrpcDataFileGeneratorContract::class, new AstGrpcDataFileGenerator());

        $callback = new SindriCommandServiceProvider()->publishers()[GenerateDataFromConfigCommand::class];
        $callback($container);

        $command = $container->getSingleton(GenerateDataFromConfigCommand::class);

        self::assertInstanceOf(GenerateDataFromConfigCommand::class, $command);

        // The gRPC reader and generator must be the container's own, not the constructor defaults,
        // or the command generates gRPC data with an unconfigured pair.
        self::assertSame(
            $container->getSingleton(GrpcRouteAttributeReaderContract::class),
            new ReflectionProperty($command, 'grpcRouteAttributeReader')->getValue($command)
        );
        self::assertSame(
            $container->getSingleton(GrpcDataFileGeneratorContract::class),
            new ReflectionProperty($command, 'grpcGenerator')->getValue($command)
        );
    }
}
