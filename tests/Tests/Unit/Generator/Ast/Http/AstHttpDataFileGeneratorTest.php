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

namespace Sindri\Tests\Unit\Generator\Ast\Http;

use Closure;
use LogicException;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Sindri\Ast\Data\HttpParameterData;
use Sindri\Ast\Data\HttpRouteData;
use Sindri\Generator\Ast\Http\AstHttpDataFileGenerator;
use Sindri\Generator\Enum\GenerateStatus;
use Sindri\Generator\Throwable\Exception\GeneratorUnreachableException;
use Sindri\Tests\Fixtures\Http\TestRegexConstantsFixture;
use Sindri\Tests\Unit\Abstract\TestCase;
use Valkyrja\Http\Message\Enum\RequestMethod;
use Valkyrja\Http\Routing\Data\DynamicRoute;
use Valkyrja\Http\Routing\Data\Parameter;
use Valkyrja\Http\Routing\Data\Route;
use Valkyrja\Http\Routing\Processor\Contract\ProcessorContract;

use function file_get_contents;
use function sys_get_temp_dir;
use function unlink;

final class AstHttpDataFileGeneratorTest extends TestCase
{
    public function testGenerateClassContentsWithEmptyDataContainsDataClass(): void
    {
        $generator = new AstHttpDataFileGenerator();
        $contents  = $generator->generateClassContents([], []);

        self::assertStringContainsString('HttpRoutingData', $contents);
    }

    public function testGenerateClassContentsWithEmptyDataContainsRoutesKey(): void
    {
        $generator = new AstHttpDataFileGenerator();
        $contents  = $generator->generateClassContents([], []);

        self::assertStringContainsString('routes:', $contents);
    }

    public function testGenerateClassContentsWithRouteContainsRouteKey(): void
    {
        $generator  = new AstHttpDataFileGenerator();
        $routeData  = new HttpRouteData(path: '/test', name: 'test.route');
        $contents   = $generator->generateClassContents(
            ['test.route' => new String_('route-expr')],
            ['test.route' => $routeData],
        );

        self::assertStringContainsString("'test.route'", $contents);
    }

    public function testGenerateClassContentsWithConstantKeyOutputsConstantReference(): void
    {
        $generator = new AstHttpDataFileGenerator();
        $routeData = new HttpRouteData(path: '/test', name: 'Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME');
        $contents  = $generator->generateClassContents(
            ['Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME' => new String_('route-expr')],
            ['Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME' => $routeData],
        );

        self::assertStringContainsString('\\Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME', $contents);
        self::assertStringNotContainsString("'Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME'", $contents);
    }

    public function testGenerateClassContentsAppendsComputedRegexForDynamicNewExprRoute(): void
    {
        $generator = new AstHttpDataFileGenerator();
        $parameter = new HttpParameterData(name: 'id', regex: '[0-9]+');
        $routeData = new HttpRouteData(
            path: '/items/{id}',
            name: 'items.show',
            isDynamic: true,
            parameters: [$parameter],
        );
        $routeExpr = new New_(new FullyQualified(Route::class), []);

        $contents = $generator->generateClassContents(
            ['items.show' => $routeExpr],
            ['items.show' => $routeData],
        );

        self::assertStringContainsString('regex', $contents);
    }

    public function testGenerateFileWithConstantRouteNameUsesConstantReferenceInPathsMap(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppHttpRoutingDataConstant' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $routeData = new HttpRouteData(
            path: '/home',
            name: 'Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME',
            requestMethods: ['Valkyrja\\Http\\Message\\Enum\\RequestMethod::GET'],
            isDynamic: false,
        );

        $generator = new AstHttpDataFileGenerator();
        $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: ['Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME' => new String_('route-expr')],
            routeData: ['Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME' => $routeData],
        );

        $contents = (string) file_get_contents($filePath);
        @unlink($filePath);

        self::assertStringContainsString('\\Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME', $contents);
        self::assertStringNotContainsString("'Valkyrja\\Http\\Routing\\Constant\\RouteName::HOME'", $contents);
    }

    public function testGenerateFileReturnsSuccessOnNewFile(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppHttpRoutingDataTest' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $generator = new AstHttpDataFileGenerator();
        $status    = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: [],
            routeData: [],
        );

        @unlink($filePath);

        self::assertSame(GenerateStatus::SUCCESS, $status);
    }

    public function testGenerateFileReturnsSkippedOnSameContent(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppHttpRoutingDataSkip' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $generator = new AstHttpDataFileGenerator();
        $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: [],
            routeData: [],
        );

        $status = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: [],
            routeData: [],
        );

        @unlink($filePath);

        self::assertSame(GenerateStatus::SKIPPED, $status);
    }

    public function testGenerateFileWithStaticRouteProducesPathsEntry(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppHttpRoutingDataStatic' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $routeData = new HttpRouteData(
            path: '/static',
            name: 'static.route',
            requestMethods: ['Valkyrja\\Http\\Message\\Enum\\RequestMethod::GET'],
            isDynamic: false,
        );

        $generator = new AstHttpDataFileGenerator();
        $status    = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: ['static.route' => new String_('route-expr')],
            routeData: ['static.route' => $routeData],
        );

        $contents = (string) file_get_contents($filePath);
        @unlink($filePath);

        self::assertSame(GenerateStatus::SUCCESS, $status);
        self::assertStringContainsString('/static', $contents);
    }

    public function testGenerateFileWithDynamicRouteProducesDynamicPathsEntry(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppHttpRoutingDataDynamic' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $parameter = new HttpParameterData(name: 'id', regex: '[0-9]+');
        $routeData = new HttpRouteData(
            path: '/items/{id}',
            name: 'items.show',
            requestMethods: ['Valkyrja\\Http\\Message\\Enum\\RequestMethod::GET'],
            isDynamic: true,
            parameters: [$parameter],
        );

        $generator = new AstHttpDataFileGenerator();
        $status    = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: ['items.show' => new String_('route-expr')],
            routeData: ['items.show' => $routeData],
        );

        $contents = (string) file_get_contents($filePath);
        @unlink($filePath);

        self::assertSame(GenerateStatus::SUCCESS, $status);
        self::assertStringContainsString('/items/{id}', $contents);
    }

    public function testGenerateFileWithStaticAnyMethodRouteExpandsToEveryConcreteMethod(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppHttpRoutingDataAny' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $routeData = new HttpRouteData(
            path: '/any',
            name: 'any.route',
            requestMethods: ['Valkyrja\\Http\\Message\\Enum\\RequestMethod::ANY'],
            isDynamic: false,
        );

        $generator = new AstHttpDataFileGenerator();
        $status    = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: ['any.route' => new String_('route-expr')],
            routeData: ['any.route' => $routeData],
        );

        $contents = (string) file_get_contents($filePath);
        @unlink($filePath);

        self::assertSame(GenerateStatus::SUCCESS, $status);

        // Registered under every concrete method, exactly as RouteCollection does at runtime.
        foreach (RequestMethod::all() as $requestMethod) {
            self::assertStringContainsString("'{$requestMethod->value}' =>", $contents);
        }

        // Never keyed under a literal ANY, which no request could ever match.
        self::assertStringNotContainsString("'" . RequestMethod::ANY->value . "' =>", $contents);
    }

    public function testGenerateFileWithDynamicAnyMethodRouteExpandsToEveryConcreteMethod(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppHttpRoutingDataAnyDynamic' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $parameter = new HttpParameterData(name: 'id', regex: '[0-9]+');
        $routeData = new HttpRouteData(
            path: '/any/{id}',
            name: 'any.show',
            requestMethods: ['Valkyrja\\Http\\Message\\Enum\\RequestMethod::ANY'],
            isDynamic: true,
            parameters: [$parameter],
        );

        $generator = new AstHttpDataFileGenerator();
        $status    = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: ['any.show' => new String_('route-expr')],
            routeData: ['any.show' => $routeData],
        );

        $contents = (string) file_get_contents($filePath);
        @unlink($filePath);

        self::assertSame(GenerateStatus::SUCCESS, $status);

        foreach (RequestMethod::all() as $requestMethod) {
            self::assertStringContainsString("'{$requestMethod->value}' =>", $contents);
        }

        self::assertStringNotContainsString("'" . RequestMethod::ANY->value . "' =>", $contents);
    }

    // -----------------------------------------------------------------------
    // computeRegex — returns '' when processor returns non-DynamicRoute (line 264)
    // -----------------------------------------------------------------------

    public function testComputeRegexReturnsEmptyWhenProcessorReturnsNonDynamicRoute(): void
    {
        $mockProcessor = $this->createMock(ProcessorContract::class);
        $mockProcessor->expects($this->once())
            ->method('route')
            ->willReturn(new Route(
                path: '/static',
                name: 'test',
                handler: static fn (): never => throw new LogicException('unreachable'),
            ));

        $generator = new class($mockProcessor) extends AstHttpDataFileGenerator {
            public function __construct(ProcessorContract $processor)
            {
                parent::__construct(processor: $processor);
            }

            public function callComputeRegex(HttpRouteData $data): string
            {
                return $this->computeRegex($data);
            }
        };

        $parameter = new HttpParameterData(name: 'id', regex: '[0-9]+');
        $routeData = new HttpRouteData(path: '/items/{id}', name: 'items', isDynamic: true, parameters: [$parameter]);

        $result = $generator->callComputeRegex($routeData);

        self::assertSame('', $result);
    }

    public function testComputeRegexUsesFallbackPathAndNameWhenEmpty(): void
    {
        $mockProcessor = $this->createMock(ProcessorContract::class);
        $mockProcessor->expects($this->once())
            ->method('route')
            ->with(self::callback(static function (DynamicRoute $route): bool {
                // Empty path/name fall back to '/' and 'temp' (the ternary false arms).
                return $route->getPath() === '/' && $route->getName() === 'temp';
            }))
            ->willReturn(new Route(
                path: '/',
                name: 'temp',
                handler: static fn (): never => throw new LogicException('unreachable'),
            ));

        $generator = new class($mockProcessor) extends AstHttpDataFileGenerator {
            public function __construct(ProcessorContract $processor)
            {
                parent::__construct(processor: $processor);
            }

            public function callComputeRegex(HttpRouteData $data): string
            {
                return $this->computeRegex($data);
            }
        };

        $routeData = new HttpRouteData(path: '', name: '', isDynamic: true, parameters: []);

        self::assertSame('', $generator->callComputeRegex($routeData));
    }

    public function testGenerateClassContentsAppendsEmptyRegexForDynamicRouteWithoutParameters(): void
    {
        $generator = new AstHttpDataFileGenerator();
        $routeData = new HttpRouteData(
            path: '/static',
            name: 'static.route',
            isDynamic: true,
            parameters: [],
        );
        $routeExpr = new New_(new FullyQualified(Route::class), []);

        $contents = $generator->generateClassContents(
            ['static.route' => $routeExpr],
            ['static.route' => $routeData],
        );

        // parameters === [] → computeRegex is skipped → an empty regex arg is appended.
        self::assertStringContainsString("regex: ''", $contents);
    }

    public function testUnreachableRouteHandlerThrows(): void
    {
        $generator = new class extends AstHttpDataFileGenerator {
            public function callUnreachableRouteHandler(): Closure
            {
                return $this->unreachableRouteHandler();
            }
        };

        $this->expectException(GeneratorUnreachableException::class);
        $this->expectExceptionMessage('unreachable');

        ($generator->callUnreachableRouteHandler())();
    }

    // -----------------------------------------------------------------------
    // buildRegexes — skips entry when computeRegex returns '' (line 206)
    // -----------------------------------------------------------------------

    public function testBuildRegexesSkipsEntryWhenComputeRegexReturnsEmpty(): void
    {
        $generator = new class extends AstHttpDataFileGenerator {
            protected function computeRegex(HttpRouteData $data): string
            {
                return '';
            }

            /** @return array<string, array<string, string>> */
            public function callBuildRegexes(array $routeData): array
            {
                return $this->buildRegexes($routeData);
            }
        };

        $parameter = new HttpParameterData(name: 'id', regex: '[0-9]+');
        $routeData = new HttpRouteData(
            path: '/items/{id}',
            name: 'items.show',
            requestMethods: ['Valkyrja\\Http\\Message\\Enum\\RequestMethod::GET'],
            isDynamic: true,
            parameters: [$parameter],
        );

        $result = $generator->callBuildRegexes(['items.show' => $routeData]);

        self::assertSame([], $result);
    }

    // -----------------------------------------------------------------------
    // buildParameter — resolves defined class constant regex (lines 279, 281, 282)
    // -----------------------------------------------------------------------

    public function testBuildParameterResolvesDefinedClassConstantRegex(): void
    {
        $generator = new class extends AstHttpDataFileGenerator {
            public function callBuildParameter(HttpParameterData $data): Parameter
            {
                return $this->buildParameter($data);
            }
        };

        $data   = new HttpParameterData(name: 'slug', regex: TestRegexConstantsFixture::class . '::ALPHA_REGEX');
        $result = $generator->callBuildParameter($data);

        self::assertSame('[a-z]+', $result->getRegex());
    }
}
