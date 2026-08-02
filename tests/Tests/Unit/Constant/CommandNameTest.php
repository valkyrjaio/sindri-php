<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Constant;

use Sindri\Constant\CommandName;
use Sindri\Tests\Unit\Abstract\TestCase;

final class CommandNameTest extends TestCase
{
    public function testDataGenerateConstant(): void
    {
        self::assertSame('data:generate', CommandName::DATA_GENERATE);
    }
}
