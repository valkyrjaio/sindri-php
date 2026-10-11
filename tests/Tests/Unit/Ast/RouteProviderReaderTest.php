<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Ast;

use PhpParser\Node\Expr\New_;
use PhpParser\PrettyPrinter\Standard;
use Sindri\Ast\Data\Result\RouteProviderResult;
use Sindri\Ast\RouteProviderReader;
use Sindri\Tests\Fixtures\Cli\Controller\TestCliControllerFixture;
use Sindri\Tests\Fixtures\Cli\Provider\ManualRouteProviderFixture;
use Sindri\Tests\Fixtures\Cli\Provider\UnsupportedRouteProviderFixture;
use Sindri\Tests\Unit\Abstract\TestCase;

use function array_map;
use function file_put_contents;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class RouteProviderReaderTest extends TestCase
{
    /** Fixture using ::class syntax — produces a real FQN in controllerClasses. */
    private static string $classFixtureFile;

    /** Legacy fixture using a plain string — controllerClasses comes back empty. */
    private static string $stringFixtureFile;

    public static function setUpBeforeClass(): void
    {
        /** @var non-empty-string $path */
        $path = realpath(__DIR__ . '/../../Fixtures/Cli/Provider/TestRouteProviderFixture.php');

        self::$classFixtureFile = $path;

        /** @var non-empty-string $path */
        $path = realpath(__DIR__ . '/../../Fixtures/Cli/Provider/RouteProviderFixture.php');

        self::$stringFixtureFile = $path;
    }

    public function testReadFileExtractsControllerClasses(): void
    {
        $result = new RouteProviderReader()->readFile(self::$classFixtureFile);

        self::assertSame([TestCliControllerFixture::class], $result->controllerClasses);
    }

    public function testReadFileExtractsEmptyControllerClassesForPlainStringFixture(): void
    {
        // 'AControllerClass' is a plain string, not a ::class constant fetch, so
        // extractClassListFromValues returns an empty array for this fixture.
        $result = new RouteProviderReader()->readFile(self::$stringFixtureFile);

        self::assertSame([], $result->controllerClasses);
    }

    public function testReadFileExtractsEmptyRoutesWhenGetRoutesReturnsAnEmptyArray(): void
    {
        $result = new RouteProviderReader()->readFile(self::$classFixtureFile);

        self::assertSame([], $result->routes);
    }

    public function testReadFileExtractsManualRoutesWithEveryNameFullyQualified(): void
    {
        /** @var non-empty-string $path */
        $path = realpath(__DIR__ . '/../../Fixtures/Cli/Provider/ManualRouteProviderFixture.php');

        $provider = ManualRouteProviderFixture::class;

        self::assertSame(
            [
                "new \\Valkyrja\\Cli\\Routing\\Data\\Route(name: 'manual:named', description: 'Keyed by a named first argument', handler: [\\$provider::class, 'handler'])",
                "new \\Valkyrja\\Cli\\Routing\\Data\\Route('manual:positional', 'Keyed by a positional first argument', [\\$provider::class, 'handler'])",
                "new \\Valkyrja\\Cli\\Routing\\Data\\Route('test:cli-route', 'Takes the name of an attribute route', [\\$provider::class, 'handler'])",
            ],
            $this->printRoutes(new RouteProviderReader()->readFile($path)),
        );
    }

    public function testReadFileKeepsOnlyTheConstructionsAmongTheReturnedItems(): void
    {
        /** @var non-empty-string $path */
        $path = realpath(__DIR__ . '/../../Fixtures/Cli/Provider/UnsupportedRouteProviderFixture.php');

        $provider = UnsupportedRouteProviderFixture::class;

        // self::buildRoute() is a call, not a construction, so the reader drops it. The
        // constructions it keeps are the generator's to accept or to skip.
        self::assertSame(
            [
                "new \\Valkyrja\\Cli\\Routing\\Data\\Route(\\Sindri\\Tests\\Fixtures\\TestRouteConstantsFixture::CLI_NAME, 'The name is not a string literal', [\\$provider::class, 'handler'])",
                "new (\\Valkyrja\\Cli\\Routing\\Data\\Route::class)('manual:expression-class', 'The construction names no class', [\\$provider::class, 'handler'])",
                "new \\Valkyrja\\Cli\\Routing\\Data\\Route(description: 'The name argument is absent', handler: [\\$provider::class, 'handler'])",
            ],
            $this->printRoutes(new RouteProviderReader()->readFile($path)),
        );
    }

    public function testReadFileQualifiesAGroupUseImport(): void
    {
        $result = $this->readSource(
            "<?php\nnamespace Test;\nuse Vendor\\Routing\\{Route};\n"
            . "class P { public function getRoutes(): array { return [new Route('r')]; } }\n",
        );

        self::assertSame(["new \\Vendor\\Routing\\Route('r')"], $this->printRoutes($result));
    }

    public function testReadFileDropsAConstructionThatSpreadsAnArgument(): void
    {
        $result = $this->readSource(
            "<?php\nnamespace Test;\nuse Vendor\\Routing\\Route;\n"
            . "class P { public function getRoutes(): array { \$a = ['r']; return [new Route(...\$a)]; } }\n",
        );

        self::assertSame([], $result->routes);
    }

    public function testReadFileDropsAConstructionWithAFirstClassCallablePlaceholder(): void
    {
        // php-parser parses `new Route(...)`, which PHP itself rejects, and leaves a placeholder
        // where the arguments belong.
        $result = $this->readSource(
            "<?php\nnamespace Test;\nuse Vendor\\Routing\\Route;\n"
            . "class P { public function getRoutes(): array { return [new Route(...)]; } }\n",
        );

        self::assertSame([], $result->routes);
    }

    public function testReadFileDropsAConstructionThatReadsAVariable(): void
    {
        $result = $this->readSource(
            "<?php\nnamespace Test;\nuse Vendor\\Routing\\Route;\n"
            . "class P { public function getRoutes(): array { \$m = []; return [new Route('r', \$m)]; } }\n",
        );

        self::assertSame([], $result->routes);
    }

    public function testReadFileDropsAConstructionThatReadsAnAncestorsConstant(): void
    {
        // The provider reads a constant of the class it extends, and the generated file is
        // outside both scopes.
        $result = $this->readSource(
            "<?php\nnamespace Test;\nuse Vendor\\Routing\\Route;\n"
            . 'class P extends Base { public function getRoutes(): array '
            . "{ return [new Route('r', Base::MIDDLEWARE)]; } }\n",
        );

        self::assertSame([], $result->routes);
    }

    public function testReadFileDropsAConstructionThatReadsTheProvidersOwnConstant(): void
    {
        $result = $this->readSource(
            "<?php\nnamespace Test;\nuse Vendor\\Routing\\Route;\n"
            . 'class P { private const array M = []; public function getRoutes(): array '
            . "{ return [new Route('r', self::M)]; } }\n",
        );

        self::assertSame([], $result->routes);
    }

    public function testReadFileDropsAConstructionThatNamesAnUnresolvableClass(): void
    {
        $result = $this->readSource(
            "<?php\nnamespace Test;\nuse Vendor\\Routing\\Route;\n"
            . "class P { public function getRoutes(): array { return [new Route([parent::class, 'h'])]; } }\n",
        );

        self::assertSame([], $result->routes);
    }

    public function testReadFileReturnsEmptyResultForFileWithNoClass(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sindri_test') . '.php';
        file_put_contents($tmpFile, "<?php\ndeclare(strict_types=1);\nnamespace Test;\n");

        $result = new RouteProviderReader()->readFile($tmpFile);

        @unlink($tmpFile);

        self::assertSame([], $result->controllerClasses);
        self::assertSame([], $result->routes);
    }

    public function testReadFileReturnsEmptyRoutesForProviderWithNoGetRoutesMethod(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sindri_test') . '.php';
        file_put_contents($tmpFile, "<?php\ndeclare(strict_types=1);\nnamespace Test;\nclass P {}\n");

        $result = new RouteProviderReader()->readFile($tmpFile);

        @unlink($tmpFile);

        self::assertSame([], $result->routes);
    }

    public function testReadFileReturnsEmptyRoutesForGetRoutesThatReturnsNoArray(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sindri_test') . '.php';
        file_put_contents(
            $tmpFile,
            "<?php\ndeclare(strict_types=1);\nnamespace Test;\nclass P { public static function getRoutes(): array { return self::build(); } }\n",
        );

        $result = new RouteProviderReader()->readFile($tmpFile);

        @unlink($tmpFile);

        self::assertSame([], $result->routes);
    }

    /**
     * Read a provider written as source, through a temporary file the reader can open.
     */
    private function readSource(string $source): RouteProviderResult
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sindri_test') . '.php';
        file_put_contents($tmpFile, $source);

        $result = new RouteProviderReader()->readFile($tmpFile);

        @unlink($tmpFile);

        return $result;
    }

    /**
     * Print each extracted route, so an assertion reads the qualified source the generator emits.
     *
     * @return string[]
     */
    private function printRoutes(RouteProviderResult $result): array
    {
        $printer = new Standard();

        return array_map(
            static fn (New_ $route): string => $printer->prettyPrintExpr($route),
            $result->routes,
        );
    }
}
