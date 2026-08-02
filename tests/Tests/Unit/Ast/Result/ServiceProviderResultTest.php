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

use Sindri\Ast\Data\Result\ServiceProviderResult;
use Sindri\Tests\Unit\Abstract\TestCase;

final class ServiceProviderResultTest extends TestCase
{
    public function testDefaultConstructorHasEmptyArrays(): void
    {
        $result = new ServiceProviderResult();

        self::assertSame([], $result->serviceClasses);
        self::assertSame([], $result->publishers);
    }

    public function testMergeDeduplicatesServiceClasses(): void
    {
        $a = new ServiceProviderResult(serviceClasses: ['ClassA', 'ClassB']);
        $b = new ServiceProviderResult(serviceClasses: ['ClassB', 'ClassC']);

        $merged = $a->merge($b);

        self::assertSame(['ClassA', 'ClassB', 'ClassC'], $merged->serviceClasses);
    }

    public function testMergeCombinesPublishers(): void
    {
        $a = new ServiceProviderResult(publishers: ['ClassA' => ['ProviderA', 'publishA']]);
        $b = new ServiceProviderResult(publishers: ['ClassB' => ['ProviderB', 'publishB']]);

        $merged = $a->merge($b);

        self::assertSame(['ProviderA', 'publishA'], $merged->publishers['ClassA']);
        self::assertSame(['ProviderB', 'publishB'], $merged->publishers['ClassB']);
    }
}
