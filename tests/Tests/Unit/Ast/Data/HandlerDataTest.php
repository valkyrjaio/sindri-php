<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Ast\Data;

use Sindri\Ast\Data\HandlerData;
use Sindri\Tests\Unit\Abstract\TestCase;

final class HandlerDataTest extends TestCase
{
    public function testConstructorStoresClass(): void
    {
        $data = new HandlerData(class: 'SomeClass', method: 'someMethod');

        self::assertSame('SomeClass', $data->class);
    }

    public function testConstructorStoresMethod(): void
    {
        $data = new HandlerData(class: 'SomeClass', method: 'someMethod');

        self::assertSame('someMethod', $data->method);
    }
}
