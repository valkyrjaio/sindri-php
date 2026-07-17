<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * (c) Melech Mizrachi <melechmizrachi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sindri\Tests\Unit\Generate;

use Override;
use Sindri\Ast\Data\Result\ConfigResult;
use Sindri\Generate\Abstract\GenerateDataFromAst;
use Sindri\Tests\Fixtures\Provider\Sub\TestOtherServiceProviderFixture;
use Sindri\Tests\Fixtures\Provider\Sub\TestServiceProviderFixture;
use Sindri\Tests\Fixtures\Provider\TestFirstComponentProviderFixture;
use Sindri\Tests\Fixtures\Provider\TestSecondComponentProviderFixture;
use Sindri\Tests\Unit\Abstract\TestCase;
use Valkyrja\Cli\Interaction\Output\Factory\Contract\OutputFactoryContract;
use Valkyrja\Cli\Routing\Data\Contract\RouteContract;

use function realpath;

final class WalkComponentProvidersTest extends TestCase
{
    private static string $providerFixtureDir;

    public static function setUpBeforeClass(): void
    {
        /** @var non-empty-string $path */
        $path = realpath(__DIR__ . '/../../Fixtures/Provider');

        self::$providerFixtureDir = $path;
    }

    public function testServiceProvidersFollowConfigDeclarationOrder(): void
    {
        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Provider',
            dir: self::$providerFixtureDir,
            providers: [
                TestFirstComponentProviderFixture::class,
                TestSecondComponentProviderFixture::class,
            ],
        );

        $outputFactory = $this->createMock(OutputFactoryContract::class);
        $outputFactory->expects($this->never())->method('createOutput');

        $route = $this->createMock(RouteContract::class);
        $route->expects($this->never())->method('getDescription');
        $route->expects($this->never())->method('getName');

        $walker = new class($outputFactory, $route) extends GenerateDataFromAst {
            #[Override]
            protected function getConfigFilePath(): string
            {
                return '';
            }

            public function walk(ConfigResult $config): array
            {
                return $this->walkComponentProviders($config)->serviceProviders;
            }
        };

        $result = $walker->walk($config);

        self::assertSame(
            [TestServiceProviderFixture::class, TestOtherServiceProviderFixture::class],
            $result,
        );
    }

    public function testServiceProviderOrderIsReversedWhenConfigOrderIsReversed(): void
    {
        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Provider',
            dir: self::$providerFixtureDir,
            providers: [
                TestSecondComponentProviderFixture::class,
                TestFirstComponentProviderFixture::class,
            ],
        );

        $outputFactory = $this->createMock(OutputFactoryContract::class);
        $outputFactory->expects($this->never())->method('createOutput');

        $route = $this->createMock(RouteContract::class);
        $route->expects($this->never())->method('getDescription');
        $route->expects($this->never())->method('getName');

        $walker = new class($outputFactory, $route) extends GenerateDataFromAst {
            #[Override]
            protected function getConfigFilePath(): string
            {
                return '';
            }

            public function walk(ConfigResult $config): array
            {
                return $this->walkComponentProviders($config)->serviceProviders;
            }
        };

        $result = $walker->walk($config);

        self::assertSame(
            [TestOtherServiceProviderFixture::class, TestServiceProviderFixture::class],
            $result,
        );
    }
}
