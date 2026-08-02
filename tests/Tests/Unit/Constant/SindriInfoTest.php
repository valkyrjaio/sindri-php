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

use Sindri\Constant\SindriInfo;
use Sindri\Tests\Unit\Abstract\TestCase;

final class SindriInfoTest extends TestCase
{
    public function testVersionIsNonEmptyString(): void
    {
        self::assertNotSame('', SindriInfo::VERSION);
    }

    public function testVersionBuildDateTimeIsNonEmptyString(): void
    {
        self::assertNotSame('', SindriInfo::VERSION_BUILD_DATE_TIME);
    }
}
