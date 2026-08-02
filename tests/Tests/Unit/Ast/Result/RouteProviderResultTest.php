<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Ast\Result;

use PhpParser\Node\Scalar\String_;
use Sindri\Ast\Data\Result\RouteProviderResult;
use Sindri\Tests\Unit\Abstract\TestCase;

final class RouteProviderResultTest extends TestCase
{
    public function testDefaultConstructorHasEmptyArrays(): void
    {
        $result = new RouteProviderResult();

        self::assertSame([], $result->controllerClasses);
        self::assertSame([], $result->routes);
    }

    public function testMergeDeduplicatesControllerClasses(): void
    {
        $a = new RouteProviderResult(controllerClasses: ['ControllerA', 'ControllerB']);
        $b = new RouteProviderResult(controllerClasses: ['ControllerB', 'ControllerC']);

        $merged = $a->merge($b);

        self::assertSame(['ControllerA', 'ControllerB', 'ControllerC'], $merged->controllerClasses);
    }

    public function testMergeCombinesRoutes(): void
    {
        $expr1 = new String_('route1');
        $expr2 = new String_('route2');

        $a = new RouteProviderResult(routes: [$expr1]);
        $b = new RouteProviderResult(routes: [$expr2]);

        $merged = $a->merge($b);

        self::assertCount(2, $merged->routes);
    }
}
