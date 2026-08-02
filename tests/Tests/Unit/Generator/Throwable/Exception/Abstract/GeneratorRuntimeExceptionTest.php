<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Generator\Throwable\Exception\Abstract;

use Sindri\Generator\Throwable\Contract\GeneratorThrowable;
use Sindri\Generator\Throwable\Exception\Abstract\GeneratorRuntimeException;
use Sindri\Tests\Unit\Abstract\TestCase;
use Sindri\Throwable\Exception\Abstract\SindriRuntimeException;

final class GeneratorRuntimeExceptionTest extends TestCase
{
    public function testExtendsSindriRuntimeException(): void
    {
        self::assertIsA(SindriRuntimeException::class, GeneratorRuntimeException::class);
    }

    public function testImplementsGeneratorThrowable(): void
    {
        self::assertIsA(GeneratorThrowable::class, GeneratorRuntimeException::class);
    }
}
