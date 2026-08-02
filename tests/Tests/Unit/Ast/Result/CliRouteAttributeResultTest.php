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

use Sindri\Ast\Data\Result\CliRouteAttributeResult;
use Sindri\Tests\Unit\Abstract\TestCase;

final class CliRouteAttributeResultTest extends TestCase
{
    public function testDefaultConstructorHasEmptyRoutesArray(): void
    {
        $result = new CliRouteAttributeResult();

        self::assertSame([], $result->routes);
    }
}
