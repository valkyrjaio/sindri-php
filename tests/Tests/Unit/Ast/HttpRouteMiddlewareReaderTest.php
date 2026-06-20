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

namespace Sindri\Tests\Unit\Ast;

use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use Sindri\Ast\Data\HttpRouteData;
use Sindri\Ast\HttpRouteMiddlewareReader;
use Sindri\Tests\Classes\Http\Middleware\TestHttpMiddlewareClass;
use Sindri\Tests\Unit\Abstract\TestCase;
use Valkyrja\Http\Message\Enum\RequestMethod;
use Valkyrja\Http\Routing\Attribute\Route\Middleware;
use Valkyrja\Http\Routing\Attribute\Route\RequestMethod as RequestMethodAttribute;
use Valkyrja\Http\Routing\Attribute\Route\RequestStruct;
use Valkyrja\Http\Routing\Attribute\Route\ResponseStruct;

final class HttpRouteMiddlewareReaderTest extends TestCase
{
    private HttpRouteMiddlewareReader $reader;

    protected function setUp(): void
    {
        $this->reader = new HttpRouteMiddlewareReader();
    }

    // -------------------------------------------------------------------------
    // extractInlineRequestMethods
    // -------------------------------------------------------------------------

    public function testExtractInlineRequestMethodsReturnsEmptyForNonArrayArg(): void
    {
        $args = [new Arg(value: new String_('not-an-array'), name: new Identifier('requestMethods'))];

        $result = $this->reader->extractInlineRequestMethods($args, [], 'Test', 'Test\\TestClass');

        self::assertSame([], $result);
    }

    public function testExtractInlineRequestMethodsReturnsEmptyWhenArgAbsent(): void
    {
        $result = $this->reader->extractInlineRequestMethods([], [], 'Test', 'Test\\TestClass');

        self::assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // updateRequestMethods
    // -------------------------------------------------------------------------

    public function testUpdateRequestMethodsDefaultsToHeadAndGetWhenEmpty(): void
    {
        $method         = new ClassMethod(new Identifier('test'));
        $method->stmts  = [];
        $method->params = [];

        $result = $this->reader->updateRequestMethods([], $method, [], 'Test', 'Test\\TestClass');

        self::assertCount(2, $result);
        self::assertStringContainsString('HEAD', $result[0]);
        self::assertStringContainsString('GET', $result[1]);
    }

    public function testUpdateRequestMethodsPreservesProvidedMethodsAndSkipsDefault(): void
    {
        $method         = new ClassMethod(new Identifier('test'));
        $method->stmts  = [];
        $method->params = [];

        $existing = ['Valkyrja\\Http\\Message\\Enum\\RequestMethod::POST'];
        $result   = $this->reader->updateRequestMethods($existing, $method, [], 'Test', 'Test\\TestClass');

        self::assertSame($existing, $result);
    }

    // -------------------------------------------------------------------------
    // updateMiddleware
    // -------------------------------------------------------------------------

    public function testUpdateMiddlewareReturnsInputUnchangedForMethodWithNoAttributes(): void
    {
        $method         = new ClassMethod(new Identifier('test'));
        $method->stmts  = [];
        $method->params = [];

        $result = $this->reader->updateMiddleware($method, [], 'Test', 'Test\\TestClass', [], [], [], [], []);

        self::assertSame([[], [], [], [], []], $result);
    }

    // -------------------------------------------------------------------------
    // updateRequestStruct / updateResponseStruct
    // -------------------------------------------------------------------------

    public function testUpdateRequestStructReturnsNullForMethodWithNoAttribute(): void
    {
        $method         = new ClassMethod(new Identifier('test'));
        $method->stmts  = [];
        $method->params = [];

        $result = $this->reader->updateRequestStruct($method, [], 'Test', 'Test\\TestClass');

        self::assertNull($result);
    }

    public function testUpdateResponseStructReturnsNullForMethodWithNoAttribute(): void
    {
        $method         = new ClassMethod(new Identifier('test'));
        $method->stmts  = [];
        $method->params = [];

        $result = $this->reader->updateResponseStruct($method, [], 'Test', 'Test\\TestClass');

        self::assertNull($result);
    }

    // -------------------------------------------------------------------------
    // buildRouteMiddlewareArgs
    // -------------------------------------------------------------------------

    public function testBuildRouteMiddlewareArgsReturnsEmptyWhenAllMiddlewareListsAreEmpty(): void
    {
        $data   = new HttpRouteData(path: '/test', name: 'test');
        $result = $this->reader->buildRouteMiddlewareArgs($data);

        self::assertSame([], $result);
    }

    public function testBuildRouteMiddlewareArgsIncludesRouteMatchedMiddlewareWhenNonEmpty(): void
    {
        $data = new HttpRouteData(
            path: '/test',
            name: 'test',
            routeMatchedMiddleware: [TestHttpMiddlewareClass::class],
        );

        $args = $this->reader->buildRouteMiddlewareArgs($data);

        self::assertCount(1, $args);
        self::assertInstanceOf(Arg::class, $args[0]);
        self::assertInstanceOf(Identifier::class, $args[0]->name);
        self::assertSame('routeMatchedMiddleware', $args[0]->name->toString());
        self::assertInstanceOf(Array_::class, $args[0]->value);
    }

    public function testBuildRouteMiddlewareArgsIncludesAllFiveListsWhenAllNonEmpty(): void
    {
        $mw   = [TestHttpMiddlewareClass::class];
        $data = new HttpRouteData(
            path: '/test',
            name: 'test',
            routeMatchedMiddleware: $mw,
            routeDispatchedMiddleware: $mw,
            throwableCaughtMiddleware: $mw,
            sendingResponseMiddleware: $mw,
            terminatedMiddleware: $mw,
        );

        $args = $this->reader->buildRouteMiddlewareArgs($data);

        self::assertCount(5, $args);
    }

    // -------------------------------------------------------------------------
    // buildRouteStructArgs
    // -------------------------------------------------------------------------

    public function testBuildRouteStructArgsReturnsEmptyWhenBothStructsAreNull(): void
    {
        $data   = new HttpRouteData(path: '/test', name: 'test');
        $result = $this->reader->buildRouteStructArgs($data);

        self::assertSame([], $result);
    }

    public function testBuildRouteStructArgsIncludesRequestStructWhenNonNull(): void
    {
        $data = new HttpRouteData(
            path: '/test',
            name: 'test',
            requestStruct: TestHttpMiddlewareClass::class,
        );

        $args = $this->reader->buildRouteStructArgs($data);

        self::assertCount(1, $args);
        self::assertInstanceOf(Arg::class, $args[0]);
        self::assertInstanceOf(Identifier::class, $args[0]->name);
        self::assertSame('requestStruct', $args[0]->name->toString());
        self::assertInstanceOf(ClassConstFetch::class, $args[0]->value);
    }

    public function testBuildRouteStructArgsIncludesBothStructsWhenBothNonNull(): void
    {
        $data = new HttpRouteData(
            path: '/test',
            name: 'test',
            requestStruct: TestHttpMiddlewareClass::class,
            responseStruct: TestHttpMiddlewareClass::class,
        );

        $args = $this->reader->buildRouteStructArgs($data);

        self::assertCount(2, $args);
    }

    // -------------------------------------------------------------------------
    // extractInlineRequestMethods / updateRequestMethods (attribute paths)
    // -------------------------------------------------------------------------

    public function testExtractInlineRequestMethodsReadsArrayArg(): void
    {
        $array = new Array_([
            new ArrayItem(new ClassConstFetch(new FullyQualified(RequestMethod::class), new Identifier('POST'))),
        ]);
        $args = [new Arg(value: $array, name: new Identifier('requestMethods'))];

        $result = $this->reader->extractInlineRequestMethods($args, [], 'Test', 'Test\\TestClass');

        self::assertCount(1, $result);
        self::assertStringContainsString('POST', $result[0]);
    }

    public function testUpdateRequestMethodsReadsRequestMethodAttribute(): void
    {
        $method = $this->methodWithAttribute(RequestMethodAttribute::class, [
            new Arg(new ClassConstFetch(new FullyQualified(RequestMethod::class), new Identifier('POST'))),
        ]);

        $result = $this->reader->updateRequestMethods([], $method, [], 'Test', 'Test\\TestClass');

        self::assertCount(1, $result);
        self::assertStringContainsString('POST', $result[0]);
    }

    // -------------------------------------------------------------------------
    // updateMiddleware / classifyMiddleware
    // -------------------------------------------------------------------------

    public function testUpdateMiddlewareClassifiesAcrossAllContracts(): void
    {
        $method = $this->methodWithAttribute(Middleware::class, [
            new Arg(
                value: new ClassConstFetch(new FullyQualified(TestHttpMiddlewareClass::class), new Identifier('class')),
                name: new Identifier('name'),
            ),
        ]);

        [$matched, $dispatched, $throwable, $sending, $terminated] = $this->reader->updateMiddleware(
            $method,
            [],
            'Test',
            'Test\\TestClass',
            [],
            [],
            [],
            [],
            [],
        );

        self::assertContains(TestHttpMiddlewareClass::class, $matched);
        self::assertContains(TestHttpMiddlewareClass::class, $dispatched);
        self::assertContains(TestHttpMiddlewareClass::class, $throwable);
        self::assertContains(TestHttpMiddlewareClass::class, $sending);
        self::assertContains(TestHttpMiddlewareClass::class, $terminated);
    }

    public function testUpdateMiddlewareSkipsInvalidMiddlewareName(): void
    {
        $method = $this->methodWithAttribute(Middleware::class, [
            new Arg(value: new String_(''), name: new Identifier('name')),
        ]);

        [$matched] = $this->reader->updateMiddleware(
            $method,
            [],
            'Test',
            'Test\\TestClass',
            [],
            [],
            [],
            [],
            [],
        );

        self::assertSame([], $matched);
    }

    // -------------------------------------------------------------------------
    // updateRequestStruct / updateResponseStruct
    // -------------------------------------------------------------------------

    public function testUpdateRequestStructReadsStructAttribute(): void
    {
        $method = $this->methodWithAttribute(RequestStruct::class, [
            new Arg(
                value: new ClassConstFetch(new FullyQualified(TestHttpMiddlewareClass::class), new Identifier('class')),
                name: new Identifier('struct'),
            ),
        ]);

        $result = $this->reader->updateRequestStruct($method, [], 'Test', 'Test\\TestClass');

        self::assertSame(TestHttpMiddlewareClass::class, $result);
    }

    public function testUpdateRequestStructReturnsNullWhenAbsent(): void
    {
        $result = $this->reader->updateRequestStruct(new ClassMethod(new Identifier('a')), [], 'Test', 'Test\\TestClass');

        self::assertNull($result);
    }

    public function testUpdateResponseStructReadsStructAttribute(): void
    {
        $method = $this->methodWithAttribute(ResponseStruct::class, [
            new Arg(
                value: new ClassConstFetch(new FullyQualified(TestHttpMiddlewareClass::class), new Identifier('class')),
                name: new Identifier('struct'),
            ),
        ]);

        $result = $this->reader->updateResponseStruct($method, [], 'Test', 'Test\\TestClass');

        self::assertSame(TestHttpMiddlewareClass::class, $result);
    }

    public function testUpdateResponseStructReturnsNullWhenAbsent(): void
    {
        $result = $this->reader->updateResponseStruct(new ClassMethod(new Identifier('a')), [], 'Test', 'Test\\TestClass');

        self::assertNull($result);
    }

    public function testUpdateRequestStructReturnsNullWhenStructValueIsEmpty(): void
    {
        $method = $this->methodWithAttribute(RequestStruct::class, [
            new Arg(value: new String_(''), name: new Identifier('struct')),
        ]);

        // Attribute present but value resolves to '' → loop iterates, inner `if` is false → return null.
        self::assertNull($this->reader->updateRequestStruct($method, [], 'Test', 'Test\\TestClass'));
    }

    public function testUpdateResponseStructReturnsNullWhenStructValueIsEmpty(): void
    {
        $method = $this->methodWithAttribute(ResponseStruct::class, [
            new Arg(value: new String_(''), name: new Identifier('struct')),
        ]);

        // Attribute present but value resolves to '' → loop iterates, inner `if` is false → return null.
        self::assertNull($this->reader->updateResponseStruct($method, [], 'Test', 'Test\\TestClass'));
    }

    /**
     * Build a ClassMethod carrying a single attribute with the given arguments.
     *
     * @param Arg[] $args
     */
    private function methodWithAttribute(string $attributeFqn, array $args): ClassMethod
    {
        $attribute = new Attribute(new FullyQualified($attributeFqn), $args);

        return new ClassMethod(
            new Identifier('action'),
            ['attrGroups' => [new AttributeGroup([$attribute])]],
        );
    }
}
