<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Generator\Enum;

use Sindri\Generator\Enum\GenerateStatus;
use Sindri\Tests\Unit\Abstract\TestCase;

final class GenerateStatusTest extends TestCase
{
    public function testSuccessCaseExists(): void
    {
        self::assertSame(GenerateStatus::SUCCESS, GenerateStatus::SUCCESS);
    }

    public function testFailureCaseExists(): void
    {
        self::assertSame(GenerateStatus::FAILURE, GenerateStatus::FAILURE);
    }

    public function testSkippedCaseExists(): void
    {
        self::assertSame(GenerateStatus::SKIPPED, GenerateStatus::SKIPPED);
    }

    public function testThreeCasesExist(): void
    {
        self::assertCount(3, GenerateStatus::cases());
    }
}
