<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Generate;

use Override;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\ParserFactory;
use ReflectionClass;
use Sindri\Ast\ConfigReader;
use Sindri\Ast\Data\Result\ComponentProviderResult;
use Sindri\Ast\Data\Result\ConfigResult;
use Sindri\Generate\Abstract\GenerateDataFromAst;
use Sindri\Generator\Enum\GenerateStatus;
use Sindri\Tests\Fixtures\Cli\Provider\ManualRouteProviderFixture;
use Sindri\Tests\Fixtures\Cli\Provider\TestMissingControllerProviderFixture;
use Sindri\Tests\Fixtures\Cli\Provider\TestRouteProviderFixture;
use Sindri\Tests\Fixtures\Cli\Provider\UnsupportedRouteProviderFixture;
use Sindri\Tests\Fixtures\Event\Provider\TestListenerProviderFixture;
use Sindri\Tests\Fixtures\Event\Provider\TestMissingListenerProviderFixture;
use Sindri\Tests\Fixtures\Grpc\Provider\TestGrpcRouteProviderFixture;
use Sindri\Tests\Fixtures\Grpc\Provider\TestMissingControllerProviderFixture as GrpcTestMissingControllerProviderClass;
use Sindri\Tests\Fixtures\Http\Provider\ManualRouteProviderFixture as HttpManualRouteProviderClass;
use Sindri\Tests\Fixtures\Http\Provider\TestMissingControllerProviderFixture as HttpTestMissingControllerProviderClass;
use Sindri\Tests\Fixtures\Http\Provider\TestRouteProviderFixture as HttpTestRouteProviderClass;
use Sindri\Tests\Fixtures\Http\Provider\UnsupportedRouteProviderFixture as HttpUnsupportedRouteProviderClass;
use Sindri\Tests\Fixtures\Provider\Sub\TestServiceProviderFixture;
use Sindri\Tests\Fixtures\Queue\Provider\ManualQueueRouteProviderFixture;
use Sindri\Tests\Fixtures\Queue\Provider\TestMissingControllerProviderFixture as QueueMissingControllerProviderFixture;
use Sindri\Tests\Fixtures\Queue\Provider\TestQueueRouteProviderFixture;
use Sindri\Tests\Fixtures\Queue\Provider\UnsupportedQueueRouteProviderFixture;
use Sindri\Tests\Unit\Abstract\TestCase;
use Valkyrja\Cli\Interaction\Output\Contract\OutputContract;
use Valkyrja\Cli\Interaction\Output\Factory\Contract\OutputFactoryContract;
use Valkyrja\Cli\Routing\Data\Contract\RouteContract;
use Valkyrja\Http\Message\Enum\RequestMethod;
use Valkyrja\Http\Routing\Data\Route;

use function file_get_contents;
use function glob;
use function mkdir;
use function realpath;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

final class GenerateDataFromAstTest extends TestCase
{
    // -----------------------------------------------------------------------
    // fqnToFilePath
    // -----------------------------------------------------------------------

    public function testFqnToFilePathForInNamespaceClass(): void
    {
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        $result = $walker->callFqnToFilePath('App\\Provider\\SomeProvider', 'App', '/var/www/src');

        self::assertSame('/var/www/src/Provider/SomeProvider.php', $result);
    }

    public function testFqnToFilePathForOutOfNamespaceInternalClassReturnsEmpty(): void
    {
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        // stdClass is a built-in — ReflectionClass::getFileName() returns false.
        $result = $walker->callFqnToFilePath('stdClass', 'App', '/var/www/src');

        self::assertSame('', $result);
    }

    public function testFqnToFilePathForNonExistentClassReturnsEmptyString(): void
    {
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        $result = $walker->callFqnToFilePath('NonExistent\\Class\\DoesNotExist', 'Other', '/var/www/src');

        self::assertSame('', $result);
    }

    public function testFqnToFilePathForOutOfNamespaceUserClassReturnsFileName(): void
    {
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        // A real userland class outside the namespace → ReflectionClass::getFileName()
        // returns its real path (the file-not-false ternary arm).
        $result = $walker->callFqnToFilePath(self::class, 'Other', '/var/www/src');

        self::assertSame((string) new ReflectionClass(self::class)->getFileName(), $result);
    }

    // -----------------------------------------------------------------------
    // walkProvider
    // -----------------------------------------------------------------------

    public function testWalkProviderWithMissingFileReturnsEmptyResult(): void
    {
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        $config = new ConfigResult(namespace: 'App', dir: '/nonexistent/dir');
        $result = $walker->callWalkProvider('App\\Provider\\Missing', $config);

        self::assertSame([], $result->serviceProviders);
        self::assertSame([], $result->listenerProviders);
        self::assertSame([], $result->cliRouteProviders);
        self::assertSame([], $result->httpRouteProviders);
    }

    public function testWalkProviderSkipsAlreadyVisitedClass(): void
    {
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        $config  = new ConfigResult(namespace: 'App', dir: '/nonexistent/dir');
        $visited = ['App\\Provider\\Missing' => true];

        $result = $walker->callWalkProviderWithVisited('App\\Provider\\Missing', $config, $visited);

        self::assertSame([], $result->componentProviders);
    }

    public function testWalkProviderExpandsRealProviderAndSubProviders(): void
    {
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        /** @var non-empty-string $dir */
        $dir    = realpath(__DIR__ . '/../../Fixtures');
        $config = new ConfigResult(namespace: 'Sindri\\Tests\\Fixtures', dir: $dir);

        $result = $walker->callWalkProvider(
            'Sindri\\Tests\\Fixtures\\Provider\\TestComponentProviderFixture',
            $config,
        );

        self::assertNotEmpty($result->serviceProviders);
    }

    public function testWalkComponentProvidersExpandsConfiguredProviders(): void
    {
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        /** @var non-empty-string $dir */
        $dir    = realpath(__DIR__ . '/../../Fixtures');
        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures',
            dir: $dir,
            providers: ['Sindri\\Tests\\Fixtures\\Provider\\TestComponentProviderFixture'],
        );

        $result = $walker->callWalkComponentProviders($config);

        self::assertNotEmpty($result->serviceProviders);
    }

    // -----------------------------------------------------------------------
    // addMessagesForGenerateStatus
    // -----------------------------------------------------------------------

    public function testAddMessagesForGenerateStatusSuccess(): void
    {
        $output = $this->buildChainableOutputStub();
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        $result = $walker->callAddMessagesForGenerateStatus($output, GenerateStatus::SUCCESS);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testAddMessagesForGenerateStatusSkipped(): void
    {
        $output = $this->buildChainableOutputStub();
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        $result = $walker->callAddMessagesForGenerateStatus($output, GenerateStatus::SKIPPED);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testAddMessagesForGenerateStatusFailure(): void
    {
        $output = $this->buildChainableOutputStub();
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        $result = $walker->callAddMessagesForGenerateStatus($output, GenerateStatus::FAILURE);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    // -----------------------------------------------------------------------
    // generateData (full pipeline with empty config)
    // -----------------------------------------------------------------------

    public function testGenerateDataRunsFullPipelineAndReturnsOutput(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $outputFactory->method('createOutput')->willReturn($output);

        $config = new ConfigResult(
            namespace: 'App',
            dir: $tmpDir,
            dataPath: $tmpDir,
            dataNamespace: 'App\\Data',
            providers: [],
        );

        $route = $this->createMock(RouteContract::class);
        $route->expects($this->once())->method('getDescription')->willReturn('Generate Data');
        $route->expects($this->once())->method('getName')->willReturn('generate');

        $walker = new class($config, $outputFactory, $route) extends GenerateDataFromAst {
            public function __construct(
                private readonly ConfigResult $staticConfig,
                OutputFactoryContract $outputFactory,
                RouteContract $route,
            ) {
                parent::__construct(outputFactory: $outputFactory, route: $route);
            }

            #[Override]
            protected function getConfigFilePath(): string
            {
                return '';
            }

            #[Override]
            protected function walkComponentProviders(ConfigResult $config): ComponentProviderResult
            {
                return new ComponentProviderResult();
            }

            public function run(): OutputContract
            {
                // Swap config reader for one that returns our pre-built config
                $this->configReader = new class($this->staticConfig) extends ConfigReader {
                    public function __construct(private readonly ConfigResult $result)
                    {
                    }

                    #[Override]
                    public function readFile(string $filePath): ConfigResult
                    {
                        return $this->result;
                    }
                };

                return $this->generateData();
            }
        };

        $result = $walker->run();

        // Clean up generated files
        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    // -----------------------------------------------------------------------
    // generate*Data with real fixture providers
    // -----------------------------------------------------------------------

    public function testGenerateContainerDataWithRealProvider(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $classesDir */
        $classesDir = realpath(__DIR__ . '/../../Fixtures/Provider/Sub');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Provider\\Sub',
            dir: $classesDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Provider\\Sub\\Data',
        );

        $result = $walker->callGenerateContainerData(
            [TestServiceProviderFixture::class],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateEventDataWithRealProvider(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $eventDir */
        $eventDir = realpath(__DIR__ . '/../../Fixtures/Event');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Event',
            dir: $eventDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Event\\Data',
        );

        $result = $walker->callGenerateEventData(
            [TestListenerProviderFixture::class],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateCliDataWithRealProvider(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $cliDir */
        $cliDir = realpath(__DIR__ . '/../../Fixtures/Cli');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Cli',
            dir: $cliDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Cli\\Data',
        );

        $result = $walker->callGenerateCliData(
            [TestRouteProviderFixture::class],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateHttpDataWithRealProvider(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $httpDir */
        $httpDir = realpath(__DIR__ . '/../../Fixtures/Http');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Http',
            dir: $httpDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Http\\Data',
        );

        $result = $walker->callGenerateHttpData(
            [HttpTestRouteProviderClass::class],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateContainerDataWithMissingProviderFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        $config = new ConfigResult(
            namespace: 'App',
            dir: '/nonexistent',
            dataPath: $tmpDir,
            dataNamespace: 'App\\Data',
        );

        $result = $walker->callGenerateContainerData(
            ['App\\Provider\\Missing'],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateEventDataWithMissingProviderFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        $config = new ConfigResult(
            namespace: 'App',
            dir: '/nonexistent',
            dataPath: $tmpDir,
            dataNamespace: 'App\\Data',
        );

        $result = $walker->callGenerateEventData(
            ['App\\Provider\\Missing'],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateEventDataWithMissingListenerClassFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $eventDir */
        $eventDir = realpath(__DIR__ . '/../../Fixtures/Event');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Event',
            dir: $eventDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Event\\Data',
        );

        $result = $walker->callGenerateEventData(
            [TestMissingListenerProviderFixture::class],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateQueueDataWithRealProvider(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $queueDir */
        $queueDir = realpath(__DIR__ . '/../../Fixtures/Queue');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Queue',
            dir: $queueDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Queue\\Data',
        );

        $result = $walker->callGenerateQueueData(
            [TestQueueRouteProviderFixture::class],
            $config,
            $output,
        );

        $generated = (string) file_get_contents($tmpDir . '/AppQueueRoutingData.php');

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
        // The provider's handler class was walked and its attributed job collected
        self::assertStringContainsString("'SendWelcomeEmail'", $generated);
    }

    public function testGenerateQueueDataCachesTheManualProviderRoutes(): void
    {
        // A provider may declare a route by hand instead of by attribute, and
        // the cache has to carry those too or the job never resolves
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $queueDir */
        $queueDir = realpath(__DIR__ . '/../../Fixtures/Queue');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Queue',
            dir: $queueDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Queue\\Data',
        );

        $walker->callGenerateQueueData(
            [ManualQueueRouteProviderFixture::class],
            $config,
            $output,
        );

        $generated = (string) file_get_contents($tmpDir . '/AppQueueRoutingData.php');

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertStringContainsString("'manual:named' => static fn ()", $generated);
        self::assertStringContainsString("'manual:positional' => static fn ()", $generated);
        // The attribute route from the same provider's controller survives too
        self::assertStringContainsString("'SendWelcomeEmail'", $generated);
    }

    public function testGenerateQueueDataSkipsTheManualRoutesItCannotKey(): void
    {
        // A construction the cache cannot name is left out rather than guessed
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $queueDir */
        $queueDir = realpath(__DIR__ . '/../../Fixtures/Queue');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Queue',
            dir: $queueDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Queue\\Data',
        );

        $walker->callGenerateQueueData(
            [UnsupportedQueueRouteProviderFixture::class],
            $config,
            $output,
        );

        $generated = (string) file_get_contents($tmpDir . '/AppQueueRoutingData.php');

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertStringNotContainsString('manual:expression-class', $generated);
        self::assertStringNotContainsString('The name argument is absent', $generated);
        self::assertStringNotContainsString('manual:builder', $generated);
    }

    public function testGenerateQueueDataWithMissingProviderFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        $config = new ConfigResult(
            namespace: 'App',
            dir: '/nonexistent',
            dataPath: $tmpDir,
            dataNamespace: 'App\\Data',
        );

        $result = $walker->callGenerateQueueData(
            ['App\\Provider\\Missing'],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateQueueDataWithMissingControllerClassFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $queueDir */
        $queueDir = realpath(__DIR__ . '/../../Fixtures/Queue');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Queue',
            dir: $queueDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Queue\\Data',
        );

        $result = $walker->callGenerateQueueData(
            [QueueMissingControllerProviderFixture::class],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateCliDataWithMissingProviderFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        $config = new ConfigResult(
            namespace: 'App',
            dir: '/nonexistent',
            dataPath: $tmpDir,
            dataNamespace: 'App\\Data',
        );

        $result = $walker->callGenerateCliData(
            ['App\\Provider\\Missing'],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateCliDataWithMissingControllerClassFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $cliDir */
        $cliDir = realpath(__DIR__ . '/../../Fixtures/Cli');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Cli',
            dir: $cliDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Cli\\Data',
        );

        $result = $walker->callGenerateCliData(
            [TestMissingControllerProviderFixture::class],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateHttpDataWithMissingProviderFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        $config = new ConfigResult(
            namespace: 'App',
            dir: '/nonexistent',
            dataPath: $tmpDir,
            dataNamespace: 'App\\Data',
        );

        $result = $walker->callGenerateHttpData(
            ['App\\Provider\\Missing'],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateHttpDataWithMissingControllerClassFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $httpDir */
        $httpDir = realpath(__DIR__ . '/../../Fixtures/Http');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Http',
            dir: $httpDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Http\\Data',
        );

        $result = $walker->callGenerateHttpData(
            [HttpTestMissingControllerProviderClass::class],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateGrpcDataWithRealProvider(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $grpcDir */
        $grpcDir = realpath(__DIR__ . '/../../Fixtures/Grpc');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Grpc',
            dir: $grpcDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Grpc\\Data',
        );

        $result = $walker->callGenerateGrpcData(
            [TestGrpcRouteProviderFixture::class],
            $config,
            $output,
        );

        $generated = (string) file_get_contents($tmpDir . '/AppGrpcRoutingData.php');

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
        self::assertStringContainsString("'/pkg.Greeter/SayHello'", $generated);
        self::assertStringContainsString("'/pkg.Greeter/Chat'", $generated);
    }

    public function testGenerateGrpcDataWithMissingProviderFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        $config = new ConfigResult(
            namespace: 'App',
            dir: '/nonexistent',
            dataPath: $tmpDir,
            dataNamespace: 'App\\Data',
        );

        $result = $walker->callGenerateGrpcData(
            ['App\\Provider\\Missing'],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    public function testGenerateGrpcDataWithMissingControllerClassFileSkips(): void
    {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $output        = $this->buildChainableOutputStub();
        $outputFactory = self::createStub(OutputFactoryContract::class);
        $walker        = $this->makeWalker($outputFactory);

        /** @var non-empty-string $grpcDir */
        $grpcDir = realpath(__DIR__ . '/../../Fixtures/Grpc');

        $config = new ConfigResult(
            namespace: 'Sindri\\Tests\\Fixtures\\Grpc',
            dir: $grpcDir,
            dataPath: $tmpDir,
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Grpc\\Data',
        );

        $result = $walker->callGenerateGrpcData(
            [GrpcTestMissingControllerProviderClass::class],
            $config,
            $output,
        );

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        self::assertInstanceOf(OutputContract::class, $result);
    }

    // -----------------------------------------------------------------------
    // Manual provider routes — getRoutes()
    // -----------------------------------------------------------------------

    public function testGenerateCliDataCachesAnAttributeDeclaredRoute(): void
    {
        $contents = $this->generateCliDataContents([TestRouteProviderFixture::class]);

        self::assertStringContainsString("'test:cli-route' => static fn ()", $contents);
        self::assertStringContainsString('A test CLI route', $contents);
        $this->assertParsesAsPhp($contents);
    }

    public function testGenerateCliDataSkipsAManualBuilderChainRoute(): void
    {
        // A builder chain is not a bare construction, and a CLI route is keyed by the name that
        // `withName` changes, so the chain cannot be keyed from the construction it starts from.
        $contents = $this->generateCliDataContents([UnsupportedRouteProviderFixture::class]);

        self::assertStringNotContainsString('Declared by a builder chain', $contents);
        self::assertStringNotContainsString('manual:renamed', $contents);
        $this->assertParsesAsPhp($contents);
    }

    public function testGenerateCliDataCachesTheManualProviderRoutes(): void
    {
        $contents = $this->generateCliDataContents([ManualRouteProviderFixture::class]);

        $provider = ManualRouteProviderFixture::class;

        self::assertStringContainsString("'manual:named' => static fn ()", $contents);
        self::assertStringContainsString("'manual:positional' => static fn ()", $contents);
        self::assertStringContainsString(
            "new \\Valkyrja\\Cli\\Routing\\Data\\Route(name: 'manual:named', description: 'Keyed by a named first argument', handler: [\\$provider::class, 'handler'])",
            $contents,
        );
        $this->assertParsesAsPhp($contents);
    }

    public function testGenerateCliDataSkipsTheManualRoutesItCannotKey(): void
    {
        $contents = $this->generateCliDataContents([UnsupportedRouteProviderFixture::class]);

        self::assertStringNotContainsString('The name is not a string literal', $contents);
        self::assertStringNotContainsString('manual:expression-class', $contents);
        self::assertStringNotContainsString('manual:builder', $contents);
        self::assertStringNotContainsString('The name argument is absent', $contents);
        $this->assertParsesAsPhp($contents);
    }

    public function testGenerateCliDataAppliesTheManualRoutesAfterEveryAttributeRoute(): void
    {
        // The routing service provider adds every attribute route first, across every provider,
        // so a manual route wins a name collision whichever provider declares it.
        $contents = $this->generateCliDataContents([
            ManualRouteProviderFixture::class,
            TestRouteProviderFixture::class,
        ]);

        self::assertStringContainsString('Takes the name of an attribute route', $contents);
        self::assertStringNotContainsString('A test CLI route', $contents);
        $this->assertParsesAsPhp($contents);
    }

    public function testGenerateHttpDataCachesAnAttributeDeclaredRoute(): void
    {
        $contents = $this->generateHttpDataContents([HttpTestRouteProviderClass::class]);

        self::assertStringContainsString("'test.route' => static fn ()", $contents);
        self::assertStringContainsString("'/test' => 'test.route'", $contents);
        $this->assertParsesAsPhp($contents);
    }

    public function testGenerateHttpDataSkipsAManualBuilderChainRoute(): void
    {
        // An HTTP route's path, name and request methods each have a builder that changes them,
        // so a chain cannot be keyed from the construction it starts from.
        $contents = $this->generateHttpDataContents([HttpUnsupportedRouteProviderClass::class]);

        self::assertStringNotContainsString('manual.chained', $contents);
        self::assertStringNotContainsString('/manual-chained', $contents);
        $this->assertParsesAsPhp($contents);
    }

    public function testGenerateHttpDataCachesTheManualProviderRoutes(): void
    {
        $contents = $this->generateHttpDataContents([HttpManualRouteProviderClass::class]);

        self::assertStringContainsString("'manual.default' => static fn ()", $contents);
        self::assertStringContainsString("'manual.put' => static fn ()", $contents);
        self::assertStringContainsString("'manual.any' => static fn ()", $contents);
        self::assertStringContainsString("'manual.none' => static fn ()", $contents);
        $this->assertParsesAsPhp($contents);
    }

    public function testGenerateHttpDataBuildsThePathsEntryForEachManualRoute(): void
    {
        $contents = $this->generateHttpDataContents([HttpManualRouteProviderClass::class]);

        // An absent requestMethods argument gives HEAD and GET, a listed method gives that
        // method alone, ANY expands to every method, and an empty list gives no entry.
        self::assertStringContainsString(
            "'HEAD' => ['/manual-default' => 'manual.default', '/manual-any' => 'manual.any']",
            $contents,
        );
        self::assertStringContainsString(
            "'GET' => ['/manual-default' => 'manual.default', '/manual-any' => 'manual.any']",
            $contents,
        );
        self::assertStringContainsString(
            "'PUT' => ['/manual-put' => 'manual.put', '/manual-any' => 'manual.any']",
            $contents,
        );
        self::assertStringContainsString("'POST' => ['/manual-any' => 'manual.any']", $contents);
        // The route with an empty requestMethods list is cached, and no path maps to it,
        // which is what the route collection does with it at run time.
        self::assertStringContainsString("'manual.none' => static fn ()", $contents);
        self::assertStringNotContainsString("'/manual-none' =>", $contents);
        $this->assertParsesAsPhp($contents);
    }

    public function testGenerateHttpDataSkipsTheManualRoutesItCannotCache(): void
    {
        $contents = $this->generateHttpDataContents([HttpUnsupportedRouteProviderClass::class]);

        // A manual DynamicRoute stays unsupported, because the reader extracts no parameters
        // and the generator computes the regex that a dynamic route is matched by from them.
        self::assertStringNotContainsString('manual.dynamic', $contents);
        self::assertStringNotContainsString('manual.non-literal-path', $contents);
        self::assertStringNotContainsString("'/manual-non-literal-name'", $contents);
        self::assertStringNotContainsString('manual.expression-class', $contents);
        self::assertStringNotContainsString('manual.unreadable-methods', $contents);
        self::assertStringNotContainsString('manual.non-literal-method-list', $contents);
        self::assertStringNotContainsString('manual.method-class', $contents);
        self::assertStringNotContainsString('manual.string-method', $contents);
        self::assertStringNotContainsString('manual.spread', $contents);
        // A constant of the provider is read inside the provider's own scope. The generated
        // file reads it from outside, where a constant that is not public is inaccessible.
        self::assertStringNotContainsString('manual.own-constant', $contents);
        self::assertStringNotContainsString('manual.closure-handler', $contents);
        self::assertStringNotContainsString('manual.property', $contents);
        self::assertStringNotContainsString('manual.call', $contents);
        self::assertStringContainsString('dynamicPaths: [],', $contents);
        self::assertStringContainsString('regexes: [],', $contents);
        $this->assertParsesAsPhp($contents);
    }

    public function testGetManualRouteRequestMethodsRejectsADynamicCaseName(): void
    {
        // The qualifier drops such a construction, so each guard below stays only for a caller
        // that builds the expression itself.
        self::assertNull($this->readRequestMethods(
            new ClassConstFetch(new FullyQualified(RequestMethod::class), new Variable('case')),
        ));
    }

    public function testGetManualRouteRequestMethodsRejectsACaseFetchedFromAnExpression(): void
    {
        self::assertNull($this->readRequestMethods(
            new ClassConstFetch(
                new New_(new FullyQualified(RequestMethod::class)),
                new Identifier('GET'),
            ),
        ));
    }

    public function testGetManualRouteRequestMethodsRejectsAStaticCall(): void
    {
        // Without the first guard a static call reads as a case, and the cache then holds a
        // request method that no request carries.
        self::assertNull($this->readRequestMethods(
            new StaticCall(new FullyQualified(RequestMethod::class), new Identifier('foo')),
        ));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Generate the CLI routing data from the given providers and read the generated file back.
     *
     * @param class-string[] $cliRouteProviders
     */
    private function generateCliDataContents(array $cliRouteProviders): string
    {
        /** @var non-empty-string $cliDir */
        $cliDir = realpath(__DIR__ . '/../../Fixtures/Cli');

        return $this->generateDataContents(
            'AppCliRoutingData',
            'Sindri\\Tests\\Fixtures\\Cli',
            $cliDir,
            static fn (object $walker, ConfigResult $config, OutputContract $output): OutputContract => $walker->callGenerateCliData($cliRouteProviders, $config, $output),
        );
    }

    /**
     * Generate the HTTP routing data from the given providers and read the generated file back.
     *
     * @param class-string[] $httpRouteProviders
     */
    private function generateHttpDataContents(array $httpRouteProviders): string
    {
        /** @var non-empty-string $httpDir */
        $httpDir = realpath(__DIR__ . '/../../Fixtures/Http');

        return $this->generateDataContents(
            'AppHttpRoutingData',
            'Sindri\\Tests\\Fixtures\\Http',
            $httpDir,
            static fn (object $walker, ConfigResult $config, OutputContract $output): OutputContract => $walker->callGenerateHttpData($httpRouteProviders, $config, $output),
        );
    }

    /**
     * Run one generate step into a temporary directory and return the generated source.
     *
     * @param callable(object, ConfigResult, OutputContract): OutputContract $generate
     */
    private function generateDataContents(
        string $className,
        string $namespace,
        string $dir,
        callable $generate,
    ): string {
        $tmpDir = sys_get_temp_dir() . '/' . $this->name();
        mkdir($tmpDir);

        $config = new ConfigResult(
            namespace: $namespace,
            dir: $dir,
            dataPath: $tmpDir,
            dataNamespace: $namespace . '\\Data',
        );

        $generate(
            $this->makeWalker(self::createStub(OutputFactoryContract::class)),
            $config,
            $this->buildChainableOutputStub(),
        );

        $contents = (string) file_get_contents($tmpDir . '/' . $className . '.php');

        foreach (glob($tmpDir . '/*.php') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($tmpDir);

        return $contents;
    }

    /**
     * Assert the generated source is valid PHP, because an uncompilable cache breaks the boot.
     *
     * A syntax error throws, so this also fails the test through the parser itself.
     */
    private function assertParsesAsPhp(string $contents): void
    {
        self::assertNotSame([], new ParserFactory()->createForNewestSupportedVersion()->parse($contents));
    }

    /**
     * Read the request methods of a route whose requestMethods argument holds the given item.
     *
     * @return string[]|null
     */
    private function readRequestMethods(Expr $item): array|null
    {
        $walker = $this->makeWalker(self::createStub(OutputFactoryContract::class));

        return $walker->callGetManualRouteRequestMethods(new New_(
            new FullyQualified(Route::class),
            [
                new Arg(
                    value: new Array_([new ArrayItem($item)]),
                    name: new Identifier('requestMethods'),
                ),
            ],
        ));
    }

    private function buildChainableOutputStub(): OutputContract
    {
        $output = self::createStub(OutputContract::class);
        $output->method('withAddedMessages')->willReturnSelf();
        $output->method('writeMessages')->willReturnSelf();

        return $output;
    }

    private function makeWalker(OutputFactoryContract $outputFactory): object
    {
        $route = $this->createMock(RouteContract::class);
        $route->expects($this->never())->method('getDescription');
        $route->expects($this->never())->method('getName');

        return new class($outputFactory, $route) extends GenerateDataFromAst {
            private array $sharedVisitedMap = [];

            #[Override]
            protected function getConfigFilePath(): string
            {
                return '';
            }

            public function callFqnToFilePath(string $fqn, string $namespace, string $srcDir): string
            {
                return $this->fqnToFilePath($fqn, $namespace, $srcDir);
            }

            public function callWalkProvider(string $providerClass, ConfigResult $config): ComponentProviderResult
            {
                $visited = [];

                return $this->walkProvider($providerClass, $config, $visited);
            }

            public function callWalkProviderWithVisited(
                string $providerClass,
                ConfigResult $config,
                array $visited,
            ): ComponentProviderResult {
                return $this->walkProvider($providerClass, $config, $visited);
            }

            public function callWalkComponentProviders(ConfigResult $config): ComponentProviderResult
            {
                return $this->walkComponentProviders($config);
            }

            public function callAddMessagesForGenerateStatus(
                OutputContract $output,
                GenerateStatus $status,
            ): OutputContract {
                return $this->addMessagesForGenerateStatus($output, $status);
            }

            public function callGenerateContainerData(
                array $serviceProviders,
                ConfigResult $config,
                OutputContract $output,
            ): OutputContract {
                return $this->generateContainerData($serviceProviders, $config, $output);
            }

            public function callGenerateEventData(
                array $listenerProviders,
                ConfigResult $config,
                OutputContract $output,
            ): OutputContract {
                return $this->generateEventData($listenerProviders, $config, $output);
            }

            public function callGenerateCliData(
                array $cliRouteProviders,
                ConfigResult $config,
                OutputContract $output,
            ): OutputContract {
                return $this->generateCliData($cliRouteProviders, $config, $output);
            }

            public function callGenerateHttpData(
                array $httpRouteProviders,
                ConfigResult $config,
                OutputContract $output,
            ): OutputContract {
                return $this->generateHttpData($httpRouteProviders, $config, $output);
            }

            /** @return string[]|null */
            public function callGetManualRouteRequestMethods(New_ $routeExpr): array|null
            {
                return $this->getManualRouteRequestMethods($routeExpr);
            }

            public function callGenerateQueueData(
                array $queueRouteProviders,
                ConfigResult $config,
                OutputContract $output,
            ): OutputContract {
                return $this->generateQueueData($queueRouteProviders, $config, $output);
            }

            public function callGenerateGrpcData(
                array $grpcRouteProviders,
                ConfigResult $config,
                OutputContract $output,
            ): OutputContract {
                return $this->generateGrpcData($grpcRouteProviders, $config, $output);
            }
        };
    }
}
