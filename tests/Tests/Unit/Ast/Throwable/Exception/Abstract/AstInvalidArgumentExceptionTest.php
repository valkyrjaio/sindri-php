<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Ast\Throwable\Exception\Abstract;

use Sindri\Ast\Throwable\Contract\AstThrowable;
use Sindri\Ast\Throwable\Exception\Abstract\AstInvalidArgumentException;
use Sindri\Tests\Unit\Abstract\TestCase;
use Sindri\Throwable\Exception\Abstract\SindriInvalidArgumentException;

final class AstInvalidArgumentExceptionTest extends TestCase
{
    public function testExtendsSindriInvalidArgumentException(): void
    {
        self::assertIsA(SindriInvalidArgumentException::class, AstInvalidArgumentException::class);
    }

    public function testImplementsAstThrowable(): void
    {
        self::assertIsA(AstThrowable::class, AstInvalidArgumentException::class);
    }
}
