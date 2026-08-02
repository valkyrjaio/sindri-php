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
use Sindri\Generator\Throwable\Exception\Abstract\GeneratorInvalidArgumentException;
use Sindri\Tests\Unit\Abstract\TestCase;
use Sindri\Throwable\Exception\Abstract\SindriInvalidArgumentException;

final class GeneratorInvalidArgumentExceptionTest extends TestCase
{
    public function testExtendsSindriInvalidArgumentException(): void
    {
        self::assertIsA(SindriInvalidArgumentException::class, GeneratorInvalidArgumentException::class);
    }

    public function testImplementsGeneratorThrowable(): void
    {
        self::assertIsA(GeneratorThrowable::class, GeneratorInvalidArgumentException::class);
    }
}
