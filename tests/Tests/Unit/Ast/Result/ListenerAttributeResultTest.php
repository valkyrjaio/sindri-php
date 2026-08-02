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

use Sindri\Ast\Data\Result\ListenerAttributeResult;
use Sindri\Tests\Unit\Abstract\TestCase;

final class ListenerAttributeResultTest extends TestCase
{
    public function testDefaultConstructorHasEmptyListenersArray(): void
    {
        $result = new ListenerAttributeResult();

        self::assertSame([], $result->listeners);
    }
}
