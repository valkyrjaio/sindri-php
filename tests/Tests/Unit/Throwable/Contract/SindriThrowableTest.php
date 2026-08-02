<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Throwable\Contract;

use Sindri\Tests\Unit\Abstract\TestCase;
use Sindri\Throwable\Contract\SindriThrowable;
use Valkyrja\Throwable\Contract\ValkyrjaThrowable;

final class SindriThrowableTest extends TestCase
{
    public function testExtendsValkyrjaThrowable(): void
    {
        self::assertIsA(ValkyrjaThrowable::class, SindriThrowable::class);
    }
}
