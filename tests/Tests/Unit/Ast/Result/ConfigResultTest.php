<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Ast\Result;

use Sindri\Ast\Data\Result\ConfigResult;
use Sindri\Tests\Unit\Abstract\TestCase;

final class ConfigResultTest extends TestCase
{
    public function testDefaultConstructorHasEmptyStrings(): void
    {
        $result = new ConfigResult();

        self::assertSame('', $result->namespace);
        self::assertSame('', $result->dir);
        self::assertSame('', $result->dataPath);
        self::assertSame('', $result->dataNamespace);
        self::assertSame([], $result->providers);
    }

    public function testExplicitValuesAreStored(): void
    {
        $result = new ConfigResult(
            namespace: 'App',
            dir: '/var/www',
            dataPath: '/var/www/data',
            dataNamespace: 'App\\Data',
            providers: ['App\\Provider'],
        );

        self::assertSame('App', $result->namespace);
        self::assertSame('/var/www', $result->dir);
        self::assertSame('/var/www/data', $result->dataPath);
        self::assertSame('App\\Data', $result->dataNamespace);
        self::assertSame(['App\\Provider'], $result->providers);
    }
}
