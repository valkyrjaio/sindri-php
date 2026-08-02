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
use Sindri\Ast\Throwable\Exception\Abstract\AstRuntimeException;
use Sindri\Tests\Unit\Abstract\TestCase;
use Sindri\Throwable\Exception\Abstract\SindriRuntimeException;

final class AstRuntimeExceptionTest extends TestCase
{
    public function testExtendsSindriRuntimeException(): void
    {
        self::assertIsA(SindriRuntimeException::class, AstRuntimeException::class);
    }

    public function testImplementsAstThrowable(): void
    {
        self::assertIsA(AstThrowable::class, AstRuntimeException::class);
    }
}
