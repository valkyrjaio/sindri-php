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

use PhpParser\PrettyPrinter\Standard;
use Sindri\Ast\GrpcRouteAttributeReader;
use Sindri\Ast\Throwable\Exception\AstNoRouteHandlerException;
use Sindri\Tests\Fixtures\Grpc\Controller\TestGrpcControllerFixture;
use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcMiddlewareFixture;
use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcResponseSentOnlyMiddlewareFixture;
use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteDispatchedOnlyMiddlewareFixture;
use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteMatchedOnlyMiddlewareFixture;
use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcSendingResponseOnlyMiddlewareFixture;
use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcThrowableCaughtOnlyMiddlewareFixture;
use Sindri\Tests\Unit\Abstract\TestCase;

use function file_put_contents;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class GrpcRouteAttributeReaderTest extends TestCase
{
    private static string $fixtureFile;

    private static string $unattributedFixtureFile;

    private static function print(mixed $expr): string
    {
        return new Standard()->prettyPrintExpr($expr);
    }

    public static function setUpBeforeClass(): void
    {
        /** @var non-empty-string $path */
        $path = realpath(__DIR__ . '/../../Fixtures/Grpc/Controller/TestGrpcControllerFixture.php');

        self::$fixtureFile = $path;

        /** @var non-empty-string $unattributedPath */
        $unattributedPath = realpath(__DIR__ . '/../../Fixtures/Grpc/Controller/TestUnattributedGrpcControllerFixture.php');

        self::$unattributedFixtureFile = $unattributedPath;
    }

    public function testKeysRoutesByTheFullyQualifiedMethod(): void
    {
        $result = new GrpcRouteAttributeReader()->readFile(self::$fixtureFile);

        self::assertArrayHasKey('/pkg.Greeter/SayHello', $result->routes);
        self::assertArrayHasKey('/pkg.Greeter/Chat', $result->routes);
    }

    public function testSkipsAnUnattributedMethod(): void
    {
        $result = new GrpcRouteAttributeReader()->readFile(self::$fixtureFile);

        self::assertCount(3, $result->routes);
        self::assertArrayNotHasKey('/pkg.Greeter/notAnRpc', $result->routes);
    }

    public function testSkipsAClassWithoutAServiceAttribute(): void
    {
        $result = new GrpcRouteAttributeReader()->readFile(self::$unattributedFixtureFile);

        self::assertSame([], $result->routes);
    }

    public function testEmitsTheHandlerTheRouteAttributeNames(): void
    {
        $result = new GrpcRouteAttributeReader()->readFile(self::$fixtureFile);

        $printed = self::print($result->routes['/pkg.Greeter/SayHello']);

        self::assertStringContainsString('method: \'/pkg.Greeter/SayHello\'', $printed);
        self::assertStringContainsString('\\' . TestGrpcControllerFixture::class . '::class, \'sayHello\'', $printed);
    }

    public function testOmitsTheStreamingFlagsForAUnaryMethod(): void
    {
        $result = new GrpcRouteAttributeReader()->readFile(self::$fixtureFile);

        $printed = self::print($result->routes['/pkg.Greeter/SayHello']);

        self::assertStringNotContainsString('clientStreaming', $printed);
        self::assertStringNotContainsString('serverStreaming', $printed);
    }

    public function testEmitsTheStreamingFlagsForABidirectionalMethod(): void
    {
        $result = new GrpcRouteAttributeReader()->readFile(self::$fixtureFile);

        $printed = self::print($result->routes['/pkg.Greeter/Chat']);

        self::assertStringContainsString('clientStreaming: true', $printed);
        self::assertStringContainsString('serverStreaming: true', $printed);
    }

    public function testClassifiesAMiddlewareIntoEveryStageItImplements(): void
    {
        $result = new GrpcRouteAttributeReader()->readFile(self::$fixtureFile);

        $printed    = self::print($result->routes['/pkg.Greeter/Chat']);
        $middleware = '\\' . TestGrpcMiddlewareFixture::class;

        self::assertStringContainsString("routeMatchedMiddleware: [$middleware::class]", $printed);
        self::assertStringContainsString("routeDispatchedMiddleware: [$middleware::class]", $printed);
        self::assertStringContainsString("throwableCaughtMiddleware: [$middleware::class]", $printed);
        self::assertStringContainsString("sendingResponseMiddleware: [$middleware::class]", $printed);
        self::assertStringContainsString("responseSentMiddleware: [$middleware::class]", $printed);
    }

    public function testASingleStageMiddlewareReachesOnlyItsOwnBucket(): void
    {
        $result = new GrpcRouteAttributeReader()->readFile(self::$fixtureFile);

        $printed    = self::print($result->routes['/pkg.Greeter/Matched']);
        $middleware = '\\' . TestGrpcRouteMatchedOnlyMiddlewareFixture::class;

        self::assertStringContainsString("routeMatchedMiddleware: [$middleware::class]", $printed);
        self::assertStringNotContainsString('routeDispatchedMiddleware:', $printed);
        self::assertStringNotContainsString('throwableCaughtMiddleware:', $printed);
        self::assertStringNotContainsString('sendingResponseMiddleware:', $printed);
        self::assertStringNotContainsString('responseSentMiddleware:', $printed);
    }

    public function testAMethodWithoutMiddlewareEmitsNone(): void
    {
        $result = new GrpcRouteAttributeReader()->readFile(self::$fixtureFile);

        $printed = self::print($result->routes['/pkg.Greeter/SayHello']);

        self::assertStringNotContainsString('Middleware:', $printed);
    }

    public function testAFileWithNoClassYieldsNoRoutes(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, "<?php\n\n// no class here\n");

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        self::assertSame([], $result->routes);
    }

    public function testARouteAttributeWithoutANameIsSkipped(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.Empty')]
            final class TmpController
            {
                #[Route(name: '')]
                public static function nameless(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        self::assertSame([], $result->routes);
    }

    public function testAServiceAttributeWithoutANameIsIgnored(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: '')]
            final class TmpController
            {
                #[Route(name: 'DoThing')]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        self::assertSame([], $result->routes);
    }

    public function testAnUnresolvableMiddlewareIsSkipped(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Route\Middleware;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.Loose')]
            final class TmpController
            {
                #[Route(name: 'DoThing', handler: [self::class, 'doThing'])]
                #[Middleware(name: 123)]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        self::assertArrayHasKey('/pkg.Loose/DoThing', $result->routes);
        self::assertStringNotContainsString('Middleware:', self::print($result->routes['/pkg.Loose/DoThing']));
    }

    public function testEmitsTheRequestAndResponseTypes(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;
            use Valkyrja\Grpc\Routing\Data\Route as DataRoute;

            #[Service(service: 'pkg.Typed')]
            final class TmpController
            {
                #[Route(
                    name: 'DoThing',
                    handler: [self::class, 'doThing'],
                    requestType: DataRoute::class,
                    responseType: Service::class,
                )]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        $printed = self::print($result->routes['/pkg.Typed/DoThing']);

        // The two name their own class, so a reader that crossed the two names fails here.
        self::assertStringContainsString('requestType: \Valkyrja\Grpc\Routing\Data\Route::class', $printed);
        self::assertStringContainsString('responseType: \Valkyrja\Grpc\Routing\Attribute\Service::class', $printed);
    }

    public function testAnEmptyRequestTypeIsDropped(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.Empty')]
            final class TmpController
            {
                #[Route(name: 'DoThing', handler: [self::class, 'doThing'], requestType: '', responseType: '')]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        $printed = self::print($result->routes['/pkg.Empty/DoThing']);

        self::assertStringNotContainsString('requestType:', $printed);
        self::assertStringNotContainsString('responseType:', $printed);
    }

    public function testAnEmptyMiddlewareNameIsSkipped(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Route\Middleware;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.EmptyMw')]
            final class TmpController
            {
                #[Route(name: 'DoThing', handler: [self::class, 'doThing'])]
                #[Middleware(name: '')]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        self::assertArrayHasKey('/pkg.EmptyMw/DoThing', $result->routes);
        self::assertStringNotContainsString('Middleware:', self::print($result->routes['/pkg.EmptyMw/DoThing']));
    }

    public function testAnExplicitHandlerAttributeWins(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Route\RouteHandler;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.Overridden')]
            final class TmpController
            {
                public static function actualHandler(): void
                {
                }

                #[Route(name: 'DoThing', handler: [self::class, 'doThing'])]
                #[RouteHandler([self::class, 'actualHandler'])]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        self::assertStringContainsString(
            'actualHandler',
            self::print($result->routes['/pkg.Overridden/DoThing'])
        );
    }

    public function testARouteThatNamesNoHandlerIsRejected(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.NoHandler')]
            final class TmpController
            {
                #[Route(name: 'DoThing')]
                public static function doThing(): void
                {
                }
            }
            PHP);

        try {
            $this->expectException(AstNoRouteHandlerException::class);
            $this->expectExceptionMessage('Route on Sindri\Tests\Tmp\TmpController::doThing names no handler');

            new GrpcRouteAttributeReader()->readFile($file);
        } finally {
            unlink($file);
        }
    }

    public function testEmitsEveryMiddlewareStageListTheRouteAttributeNames(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcResponseSentOnlyMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteDispatchedOnlyMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteMatchedOnlyMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcSendingResponseOnlyMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcThrowableCaughtOnlyMiddlewareFixture;
            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.Declared')]
            final class TmpController
            {
                #[Route(
                    name: 'DoThing',
                    handler: [self::class, 'doThing'],
                    routeMatchedMiddleware: [TestGrpcRouteMatchedOnlyMiddlewareFixture::class],
                    routeDispatchedMiddleware: [TestGrpcRouteDispatchedOnlyMiddlewareFixture::class],
                    throwableCaughtMiddleware: [TestGrpcThrowableCaughtOnlyMiddlewareFixture::class],
                    sendingResponseMiddleware: [TestGrpcSendingResponseOnlyMiddlewareFixture::class],
                    responseSentMiddleware: [TestGrpcResponseSentOnlyMiddlewareFixture::class],
                )]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        $printed = self::print($result->routes['/pkg.Declared/DoThing']);

        // Each stage names its own class, so an argument read into the wrong field fails here.
        self::assertStringContainsString(
            'routeMatchedMiddleware: [\\' . TestGrpcRouteMatchedOnlyMiddlewareFixture::class . '::class]',
            $printed
        );
        self::assertStringContainsString(
            'routeDispatchedMiddleware: [\\' . TestGrpcRouteDispatchedOnlyMiddlewareFixture::class . '::class]',
            $printed
        );
        self::assertStringContainsString(
            'throwableCaughtMiddleware: [\\' . TestGrpcThrowableCaughtOnlyMiddlewareFixture::class . '::class]',
            $printed
        );
        self::assertStringContainsString(
            'sendingResponseMiddleware: [\\' . TestGrpcSendingResponseOnlyMiddlewareFixture::class . '::class]',
            $printed
        );
        self::assertStringContainsString(
            'responseSentMiddleware: [\\' . TestGrpcResponseSentOnlyMiddlewareFixture::class . '::class]',
            $printed
        );
    }

    public function testAMiddlewareAttributeAppendsToTheStageListTheRouteAttributeNames(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteMatchedOnlyMiddlewareFixture;
            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Route\Middleware;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.Appended')]
            final class TmpController
            {
                #[Route(
                    name: 'DoThing',
                    handler: [self::class, 'doThing'],
                    routeMatchedMiddleware: [TestGrpcRouteMatchedOnlyMiddlewareFixture::class],
                )]
                #[Middleware(name: TestGrpcMiddlewareFixture::class)]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        $printed  = self::print($result->routes['/pkg.Appended/DoThing']);
        $declared = '\\' . TestGrpcRouteMatchedOnlyMiddlewareFixture::class;
        $appended = '\\' . TestGrpcMiddlewareFixture::class;

        self::assertStringContainsString(
            "routeMatchedMiddleware: [$declared::class, $appended::class]",
            $printed
        );
        self::assertStringContainsString("responseSentMiddleware: [$appended::class]", $printed);
    }

    public function testAnUnreadableHandlerAttributeLeavesTheRouteAttributesOwnHandler(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Route\RouteHandler;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.LooseHandler')]
            final class TmpController
            {
                #[Route(name: 'DoThing', handler: [self::class, 'doThing'])]
                #[RouteHandler(123)]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        self::assertStringContainsString(
            '\\Sindri\\Tests\\Tmp\\TmpController::class, \'doThing\'',
            self::print($result->routes['/pkg.LooseHandler/DoThing'])
        );
    }

    public function testEmitsTheHandlerTheRouteAttributeNamesRatherThanTheAttributedMethod(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.Declared')]
            final class TmpController
            {
                #[Route(name: 'DoThing', handler: [self::class, 'declaredHandler'])]
                public static function doThing(): void
                {
                }

                public static function declaredHandler(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        $printed = self::print($result->routes['/pkg.Declared/DoThing']);

        // The attributed method is not the handler, so a reader that derived one from the method
        // fails here.
        self::assertStringContainsString('\'declaredHandler\'', $printed);
        self::assertStringNotContainsString('\'doThing\'', $printed);
    }

    public function testReadsEveryRouteArgumentDeclaredPositionally(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcResponseSentOnlyMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteDispatchedOnlyMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteMatchedOnlyMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcSendingResponseOnlyMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcThrowableCaughtOnlyMiddlewareFixture;
            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;
            use Valkyrja\Grpc\Routing\Data\Route as DataRoute;

            #[Service(service: 'pkg.Positional')]
            final class TmpController
            {
                #[Route(
                    'DoThing',
                    [self::class, 'declaredHandler'],
                    DataRoute::class,
                    Service::class,
                    false,
                    true,
                    [TestGrpcRouteMatchedOnlyMiddlewareFixture::class],
                    [TestGrpcRouteDispatchedOnlyMiddlewareFixture::class],
                    [TestGrpcThrowableCaughtOnlyMiddlewareFixture::class],
                    [TestGrpcSendingResponseOnlyMiddlewareFixture::class],
                    [TestGrpcResponseSentOnlyMiddlewareFixture::class],
                )]
                public static function doThing(): void
                {
                }

                #[Route(
                    'DoOther',
                    [self::class, 'declaredHandler'],
                    DataRoute::class,
                    Service::class,
                    true,
                    false,
                )]
                public static function doOther(): void
                {
                }

                public static function declaredHandler(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        $printed = self::print($result->routes['/pkg.Positional/DoThing']);

        // Each slot holds its own value, so an argument read from the wrong position fails here.
        self::assertStringContainsString('\'declaredHandler\'', $printed);
        self::assertStringContainsString('requestType: \Valkyrja\Grpc\Routing\Data\Route::class', $printed);
        self::assertStringContainsString('responseType: \Valkyrja\Grpc\Routing\Attribute\Service::class', $printed);
        self::assertStringNotContainsString('clientStreaming', $printed);
        self::assertStringContainsString('serverStreaming: true', $printed);
        self::assertStringContainsString(
            'routeMatchedMiddleware: [\\' . TestGrpcRouteMatchedOnlyMiddlewareFixture::class . '::class]',
            $printed
        );
        self::assertStringContainsString(
            'routeDispatchedMiddleware: [\\' . TestGrpcRouteDispatchedOnlyMiddlewareFixture::class . '::class]',
            $printed
        );
        self::assertStringContainsString(
            'throwableCaughtMiddleware: [\\' . TestGrpcThrowableCaughtOnlyMiddlewareFixture::class . '::class]',
            $printed
        );
        self::assertStringContainsString(
            'sendingResponseMiddleware: [\\' . TestGrpcSendingResponseOnlyMiddlewareFixture::class . '::class]',
            $printed
        );
        self::assertStringContainsString(
            'responseSentMiddleware: [\\' . TestGrpcResponseSentOnlyMiddlewareFixture::class . '::class]',
            $printed
        );

        // A flag read from a slot that holds no bool reads false, which is what this route declares
        // for `clientStreaming`, so a second route reverses the pair and pins both positions.
        $other = self::print($result->routes['/pkg.Positional/DoOther']);

        self::assertStringContainsString('clientStreaming: true', $other);
        self::assertStringNotContainsString('serverStreaming', $other);
    }

    public function testReadsOneStreamingFlagWithoutTheOther(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.ClientOnly')]
            final class TmpController
            {
                #[Route(
                    name: 'DoThing',
                    handler: [self::class, 'doThing'],
                    clientStreaming: true,
                    serverStreaming: false,
                )]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        $printed = self::print($result->routes['/pkg.ClientOnly/DoThing']);

        // Only one flag is set, so a reader that crossed the two names fails here.
        self::assertStringContainsString('clientStreaming: true', $printed);
        self::assertStringNotContainsString('serverStreaming', $printed);
    }

    public function testAStageListTheReaderCannotResolveIsDropped(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteMatchedOnlyMiddlewareFixture;
            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;

            #[Service(service: 'pkg.LooseList')]
            final class TmpController
            {
                public const array MIDDLEWARE = [TestGrpcRouteMatchedOnlyMiddlewareFixture::class];

                #[Route(
                    name: 'DoThing',
                    handler: [self::class, 'doThing'],
                    routeMatchedMiddleware: [123],
                    responseSentMiddleware: self::MIDDLEWARE,
                )]
                public static function doThing(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        $printed = self::print($result->routes['/pkg.LooseList/DoThing']);

        // The generated route holds class names, so the reader leaves out a list item that is no
        // class name and a list that is no literal, rather than writing either into the cache.
        self::assertStringNotContainsString('routeMatchedMiddleware:', $printed);
        self::assertStringNotContainsString('responseSentMiddleware:', $printed);
    }

    public function testARepeatedRouteAttributeEmitsARouteForEachName(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sindri-grpc');

        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
            <?php

            namespace Sindri\Tests\Tmp;

            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcResponseSentOnlyMiddlewareFixture;
            use Sindri\Tests\Fixtures\Grpc\Middleware\TestGrpcRouteMatchedOnlyMiddlewareFixture;
            use Valkyrja\Grpc\Routing\Attribute\Route;
            use Valkyrja\Grpc\Routing\Attribute\Service;
            use Valkyrja\Grpc\Routing\Data\Route as DataRoute;

            #[Service(service: 'pkg.Aliased')]
            final class TmpController
            {
                #[Route(
                    name: 'SayHello',
                    handler: [self::class, 'sayHello'],
                    requestType: DataRoute::class,
                    clientStreaming: true,
                    routeMatchedMiddleware: [TestGrpcRouteMatchedOnlyMiddlewareFixture::class],
                )]
                #[Route(
                    name: 'Greet',
                    handler: [self::class, 'greet'],
                    responseType: Service::class,
                    serverStreaming: true,
                    responseSentMiddleware: [TestGrpcResponseSentOnlyMiddlewareFixture::class],
                )]
                public static function sayHello(): void
                {
                }

                public static function greet(): void
                {
                }
            }
            PHP);

        $result = new GrpcRouteAttributeReader()->readFile($file);

        unlink($file);

        self::assertCount(2, $result->routes);

        $first  = self::print($result->routes['/pkg.Aliased/SayHello']);
        $second = self::print($result->routes['/pkg.Aliased/Greet']);

        // The two attributes differ in every field, so a field resolved once for the method rather
        // than once for each attribute fails here, and so does a value that leaks between them.
        self::assertStringContainsString('\'sayHello\'', $first);
        self::assertStringContainsString('requestType: \Valkyrja\Grpc\Routing\Data\Route::class', $first);
        self::assertStringContainsString('clientStreaming: true', $first);
        self::assertStringContainsString(
            'routeMatchedMiddleware: [\\' . TestGrpcRouteMatchedOnlyMiddlewareFixture::class . '::class]',
            $first
        );
        self::assertStringNotContainsString('\'greet\'', $first);
        self::assertStringNotContainsString('responseType', $first);
        self::assertStringNotContainsString('serverStreaming', $first);
        self::assertStringNotContainsString('responseSentMiddleware', $first);

        self::assertStringContainsString('\'greet\'', $second);
        self::assertStringContainsString('responseType: \Valkyrja\Grpc\Routing\Attribute\Service::class', $second);
        self::assertStringContainsString('serverStreaming: true', $second);
        self::assertStringContainsString(
            'responseSentMiddleware: [\\' . TestGrpcResponseSentOnlyMiddlewareFixture::class . '::class]',
            $second
        );
        self::assertStringNotContainsString('\'sayHello\'', $second);
        self::assertStringNotContainsString('requestType', $second);
        self::assertStringNotContainsString('clientStreaming', $second);
        self::assertStringNotContainsString('routeMatchedMiddleware', $second);
    }
}
