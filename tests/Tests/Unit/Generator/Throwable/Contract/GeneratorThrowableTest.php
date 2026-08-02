<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Generator\Throwable\Contract;

use Sindri\Generator\Throwable\Contract\GeneratorThrowable;
use Sindri\Tests\Unit\Abstract\TestCase;
use Sindri\Throwable\Contract\SindriThrowable;

final class GeneratorThrowableTest extends TestCase
{
    public function testExtendsSindriThrowable(): void
    {
        self::assertIsA(SindriThrowable::class, GeneratorThrowable::class);
    }
}
