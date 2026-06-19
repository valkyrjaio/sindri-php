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

namespace Sindri\Tests\Unit\Ast\Abstract;

use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use Sindri\Ast\Abstract\RouteAttributeReader;
use Sindri\Ast\Data\HandlerData;
use Sindri\Tests\Unit\Abstract\TestCase;
use Valkyrja\Cli\Routing\Attribute\Route\RouteHandler;

final class RouteAttributeReaderTest extends TestCase
{
    /**
     * A concrete RouteAttributeReader that resolves its #[RouteHandler] attribute to
     * Valkyrja\Cli\Routing\Attribute\Route\RouteHandler and exposes the protected
     * updateHandler() for direct testing.
     */
    private RouteAttributeReader $reader;

    protected function setUp(): void
    {
        $this->reader = new class extends RouteAttributeReader {
            protected function getRouteHandlerAttributeClass(): string
            {
                return RouteHandler::class;
            }

            public function callUpdateHandler(
                ClassMethod $method,
                array $useMap,
                string $namespace,
                string $currentClass,
            ): HandlerData {
                return $this->updateHandler($method, $useMap, $namespace, $currentClass);
            }
        };
    }

    // -------------------------------------------------------------------------
    // updateHandler — no #[RouteHandler] attribute falls back to [class, method]
    // -------------------------------------------------------------------------

    public function testUpdateHandlerFallsBackToCurrentClassAndMethodWhenNoAttribute(): void
    {
        $method = new ClassMethod(new Identifier('myAction'));

        $result = $this->reader->callUpdateHandler($method, [], 'App\\Controller', 'App\\Controller\\HomeController');

        self::assertInstanceOf(HandlerData::class, $result);
        self::assertSame('App\\Controller\\HomeController', $result->class);
        self::assertSame('myAction', $result->method);
    }

    // -------------------------------------------------------------------------
    // updateHandler — #[RouteHandler([Class::class, 'method'])] returns that handler
    // -------------------------------------------------------------------------

    public function testUpdateHandlerReturnsHandlerFromAttributeArray(): void
    {
        $handlerExpr = new Array_([
            new ArrayItem(new ClassConstFetch(new FullyQualified('App\\Other'), new Identifier('class'))),
            new ArrayItem(new String_('handle')),
        ]);

        $method = $this->methodWithRouteHandlerArg($handlerExpr);

        $result = $this->reader->callUpdateHandler($method, [], 'App\\Controller', 'App\\Controller\\HomeController');

        self::assertInstanceOf(HandlerData::class, $result);
        self::assertSame('App\\Other', $result->class);
        self::assertSame('handle', $result->method);
    }

    // -------------------------------------------------------------------------
    // updateHandler — attribute present but arg not a handler array falls back
    // -------------------------------------------------------------------------

    public function testUpdateHandlerFallsBackWhenAttributeArgIsNotHandlerData(): void
    {
        $method = $this->methodWithRouteHandlerArg(new String_('not-a-handler'));

        $result = $this->reader->callUpdateHandler($method, [], 'App\\Controller', 'App\\Controller\\HomeController');

        self::assertInstanceOf(HandlerData::class, $result);
        self::assertSame('App\\Controller\\HomeController', $result->class);
        self::assertSame('myAction', $result->method);
    }

    /**
     * Build a ClassMethod named "myAction" carrying a single #[RouteHandler] attribute
     * whose first argument is the given expression.
     */
    private function methodWithRouteHandlerArg(Expr $argExpr): ClassMethod
    {
        $attribute = new Attribute(new FullyQualified(RouteHandler::class), [new Arg($argExpr)]);

        return new ClassMethod(
            new Identifier('myAction'),
            ['attrGroups' => [new AttributeGroup([$attribute])]],
        );
    }
}
