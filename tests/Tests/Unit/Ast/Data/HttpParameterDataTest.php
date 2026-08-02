<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Ast\Data;

use Sindri\Ast\Data\HttpParameterData;
use Sindri\Tests\Unit\Abstract\TestCase;

final class HttpParameterDataTest extends TestCase
{
    public function testConstructorStoresNameAndRegex(): void
    {
        $data = new HttpParameterData(name: 'id', regex: '[0-9]+');

        self::assertSame('id', $data->name);
        self::assertSame('[0-9]+', $data->regex);
    }

    public function testConstructorDefaultsCastToNull(): void
    {
        $data = new HttpParameterData(name: 'id', regex: '[0-9]+');

        self::assertNull($data->cast);
    }

    public function testConstructorDefaultsIsOptionalToFalse(): void
    {
        $data = new HttpParameterData(name: 'id', regex: '[0-9]+');

        self::assertFalse($data->isOptional);
    }

    public function testConstructorDefaultsShouldCaptureToTrue(): void
    {
        $data = new HttpParameterData(name: 'id', regex: '[0-9]+');

        self::assertTrue($data->shouldCapture);
    }
}
