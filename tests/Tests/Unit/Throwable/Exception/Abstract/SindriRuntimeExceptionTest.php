<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Throwable\Exception\Abstract;

use Sindri\Tests\Unit\Abstract\TestCase;
use Sindri\Throwable\Contract\SindriThrowable;
use Sindri\Throwable\Exception\Abstract\SindriRuntimeException;
use Valkyrja\Throwable\Exception\Abstract\ValkyrjaRuntimeException;

final class SindriRuntimeExceptionTest extends TestCase
{
    public function testExtendsValkyrjaRuntimeException(): void
    {
        self::assertIsA(ValkyrjaRuntimeException::class, SindriRuntimeException::class);
    }

    public function testImplementsSindriThrowable(): void
    {
        self::assertIsA(SindriThrowable::class, SindriRuntimeException::class);
    }
}
