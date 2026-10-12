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

use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Sindri\Ast\RouteExpressionQualifier;
use Sindri\Tests\Unit\Abstract\TestCase;

final class RouteExpressionQualifierTest extends TestCase
{
    public function testQualifiesAnImportedNameThroughTheUseMap(): void
    {
        $qualified = $this->qualify(
            "new Route('/a')",
            ['Route' => 'Vendor\\Routing\\Route'],
            'App\\Provider',
            'App\\Provider\\AppRouteProvider',
        );

        self::assertSame("new \\Vendor\\Routing\\Route('/a')", $qualified);
    }

    public function testQualifiesAnUnimportedNameAgainstTheNamespace(): void
    {
        $qualified = $this->qualify(
            "new Route('/a')",
            [],
            'App\\Provider',
            'App\\Provider\\AppRouteProvider',
        );

        self::assertSame("new \\App\\Provider\\Route('/a')", $qualified);
    }

    public function testQualifiesAnUnimportedNameAgainstTheGlobalNamespace(): void
    {
        $qualified = $this->qualify("new Route('/a')", [], '', 'AppRouteProvider');

        self::assertSame("new \\Route('/a')", $qualified);
    }

    public function testQualifiesSelfToTheProviderClass(): void
    {
        $qualified = $this->qualify(
            "[self::class, 'handler']",
            [],
            'App\\Provider',
            'App\\Provider\\AppRouteProvider',
        );

        self::assertSame("[\\App\\Provider\\AppRouteProvider::class, 'handler']", $qualified);
    }

    public function testQualifiesStaticToTheProviderClass(): void
    {
        $qualified = $this->qualify(
            "[static::class, 'handler']",
            [],
            'App\\Provider',
            'App\\Provider\\AppRouteProvider',
        );

        self::assertSame("[\\App\\Provider\\AppRouteProvider::class, 'handler']", $qualified);
    }

    public function testQualifiesAnEnumCaseThroughTheUseMap(): void
    {
        $qualified = $this->qualify(
            '[RequestMethod::PUT]',
            ['RequestMethod' => 'Vendor\\Message\\RequestMethod'],
            'App\\Provider',
            'App\\Provider\\AppRouteProvider',
        );

        self::assertSame('[\\Vendor\\Message\\RequestMethod::PUT]', $qualified);
    }

    public function testLeavesABooleanConstantUnqualified(): void
    {
        $qualified = $this->qualify(
            'new Route(true, false, null)',
            ['Route' => 'Vendor\\Routing\\Route'],
            'App\\Provider',
            'App\\Provider\\AppRouteProvider',
        );

        self::assertSame('new \\Vendor\\Routing\\Route(true, false, null)', $qualified);
    }

    public function testQualifiesAClosureParameterType(): void
    {
        $qualified = $this->qualify(
            'static fn (Container $container): Response => new Response()',
            ['Container' => 'Vendor\\Container', 'Response' => 'Vendor\\Response'],
            'App\\Provider',
            'App\\Provider\\AppRouteProvider',
        );

        self::assertSame(
            'static fn(\\Vendor\\Container $container): \\Vendor\\Response => new \\Vendor\\Response()',
            $qualified,
        );
    }

    public function testReportsAnEvaluableExpression(): void
    {
        $qualifier = $this->qualifier(['Route' => 'Vendor\\Routing\\Route']);

        $this->traverse("new Route('/a', 'a', [self::class, 'handler'], [true, false, null, 1, -1, 1.5])", $qualifier);

        self::assertTrue($qualifier->isEvaluable());
    }

    public function testReportsAVariableAsUnevaluable(): void
    {
        $qualifier = $this->qualifier();

        // The expression is written into a static closure in another class, where the
        // provider's own variable does not exist.
        $this->traverse('[$middleware]', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsACallAsUnevaluable(): void
    {
        $qualifier = $this->qualifier();

        $this->traverse('[self::middleware()]', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsAPropertyFetchAsUnevaluable(): void
    {
        $qualifier = $this->qualifier();

        $this->traverse('[$this->middleware]', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsAClosureAsUnevaluable(): void
    {
        $qualifier = $this->qualifier();

        $this->traverse('static fn (): null => null', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsTheProvidersOwnConstantAsUnevaluable(): void
    {
        $qualifier = $this->qualifier();

        $this->traverse('[self::MIDDLEWARE]', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsTheParentClassConstantAsUnevaluable(): void
    {
        $qualifier = $this->qualifier(['Base' => 'App\\Provider\\Base']);

        // The provider reads a non-public constant of its parent, and the generated file is
        // outside both scopes.
        $this->traverse('[Base::MIDDLEWARE]', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsTheProvidersOwnConstantReadFromAnInstanceAsUnevaluable(): void
    {
        $qualifier = $this->qualifier();

        // The fetch names no class, so the scope it reads cannot be told apart from the
        // provider's own, and a constant that is not public is unreachable from outside it.
        $this->traverse('[(new self())::MIDDLEWARE]', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsAForeignClassConstantAsEvaluable(): void
    {
        $qualifier = $this->qualifier(['RequestMethod' => 'Vendor\\Message\\RequestMethod']);

        $this->traverse('[RequestMethod::PUT]', $qualifier);

        self::assertTrue($qualifier->isEvaluable());
    }

    public function testReportsADynamicClassConstantAsUnevaluable(): void
    {
        $qualifier = $this->qualifier(['RequestMethod' => 'Vendor\\Message\\RequestMethod']);

        $this->traverse('[RequestMethod::{$case}]', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsAConstantFetchOnAVariableClassAsUnevaluable(): void
    {
        $qualifier = $this->qualifier();

        $this->traverse('[$class::CASE]', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsAGlobalConstantAsUnevaluable(): void
    {
        $qualifier = $this->qualifier();

        $this->traverse('[SOME_CONSTANT]', $qualifier);

        self::assertFalse($qualifier->isEvaluable());
    }

    public function testReportsParentAsUnevaluable(): void
    {
        $qualifier = $this->qualifier();

        $expression = $this->traverse("[parent::class, 'handler']", $qualifier);

        self::assertFalse($qualifier->isEvaluable());
        self::assertSame("[parent::class, 'handler']", $expression);
    }

    public function testResetsTheEvaluableReportOnEachTraversal(): void
    {
        $qualifier = $this->qualifier();

        $this->traverse('[parent::class]', $qualifier);
        $this->traverse('[self::class]', $qualifier);

        self::assertTrue($qualifier->isEvaluable());
    }

    /**
     * Qualify a single expression written as source and print the result.
     *
     * @param array<string, string> $useMap
     */
    private function qualify(string $expression, array $useMap, string $namespace, string $class): string
    {
        return $this->traverse($expression, new RouteExpressionQualifier($useMap, $namespace, $class, ''));
    }

    /**
     * Build a qualifier for a provider in `App\Provider` that extends `App\Provider\Base`.
     *
     * @param array<string, string> $useMap
     */
    private function qualifier(array $useMap = []): RouteExpressionQualifier
    {
        return new RouteExpressionQualifier(
            $useMap,
            'App\\Provider',
            'App\\Provider\\AppRouteProvider',
            'App\\Provider\\Base',
        );
    }

    /**
     * Traverse a single expression written as source with the given qualifier and print it.
     */
    private function traverse(string $expression, RouteExpressionQualifier $qualifier): string
    {
        $statements = new ParserFactory()->createForNewestSupportedVersion()->parse("<?php\n$expression;") ?? [];
        $statement  = $statements[0] ?? null;

        self::assertInstanceOf(Expression::class, $statement);

        new NodeTraverser($qualifier)->traverse([$statement->expr]);

        return new Standard()->prettyPrintExpr($statement->expr);
    }
}
