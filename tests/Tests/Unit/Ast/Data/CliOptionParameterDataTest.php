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

use Sindri\Ast\Data\CliOptionParameterData;
use Sindri\Tests\Unit\Abstract\TestCase;

final class CliOptionParameterDataTest extends TestCase
{
    public function testConstructorStoresNameAndDescription(): void
    {
        $data = new CliOptionParameterData(name: 'opt', description: 'desc');

        self::assertSame('opt', $data->name);
        self::assertSame('desc', $data->description);
    }

    public function testConstructorDefaultsValueDisplayName(): void
    {
        $data = new CliOptionParameterData(name: 'opt', description: 'desc');

        self::assertSame('', $data->valueDisplayName);
    }

    public function testConstructorDefaultsCastToNull(): void
    {
        $data = new CliOptionParameterData(name: 'opt', description: 'desc');

        self::assertNull($data->cast);
    }

    public function testConstructorDefaultsDefaultValue(): void
    {
        $data = new CliOptionParameterData(name: 'opt', description: 'desc');

        self::assertSame('', $data->defaultValue);
    }

    public function testConstructorDefaultsShortNames(): void
    {
        $data = new CliOptionParameterData(name: 'opt', description: 'desc');

        self::assertSame([], $data->shortNames);
    }

    public function testConstructorDefaultsValidValues(): void
    {
        $data = new CliOptionParameterData(name: 'opt', description: 'desc');

        self::assertSame([], $data->validValues);
    }

    public function testConstructorDefaultsMode(): void
    {
        $data = new CliOptionParameterData(name: 'opt', description: 'desc');

        self::assertSame('Valkyrja\\Cli\\Routing\\Enum\\OptionMode::OPTIONAL', $data->mode);
    }

    public function testConstructorDefaultsValueMode(): void
    {
        $data = new CliOptionParameterData(name: 'opt', description: 'desc');

        self::assertSame('Valkyrja\\Cli\\Routing\\Enum\\OptionValueMode::DEFAULT', $data->valueMode);
    }
}
