<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Ast\Throwable\Exception;

use Sindri\Ast\Throwable\Contract\AstThrowable;
use Sindri\Ast\Throwable\Exception\Abstract\AstRuntimeException;
use Sindri\Ast\Throwable\Exception\AstNoRouteHandlerException;
use Sindri\Tests\Unit\Abstract\TestCase;

final class AstNoRouteHandlerExceptionTest extends TestCase
{
    public function testExtendsAstRuntimeException(): void
    {
        self::assertInstanceOf(AstRuntimeException::class, new AstNoRouteHandlerException(__FUNCTION__));
    }

    public function testImplementsAstThrowable(): void
    {
        self::assertInstanceOf(AstThrowable::class, new AstNoRouteHandlerException(__FUNCTION__));
    }

    public function testMessageIsPreserved(): void
    {
        $exception = new AstNoRouteHandlerException(__FUNCTION__);

        self::assertSame(__FUNCTION__, $exception->getMessage());
    }

    public function testCanBeThrown(): void
    {
        $this->expectException(AstNoRouteHandlerException::class);
        $this->expectExceptionMessage(__FUNCTION__);

        throw new AstNoRouteHandlerException(__FUNCTION__);
    }
}
