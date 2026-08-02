<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Cli\Provider;

use Override;
use Sindri\Tests\Fixtures\Cli\Controller\TestCliControllerFixture;
use Valkyrja\Cli\Routing\Provider\Contract\CliRouteProviderContract;

final class TestRouteProviderFixture implements CliRouteProviderContract
{
    #[Override]
    public static function getControllerClasses(): array
    {
        return [TestCliControllerFixture::class];
    }

    #[Override]
    public static function getRoutes(): array
    {
        return [];
    }
}
