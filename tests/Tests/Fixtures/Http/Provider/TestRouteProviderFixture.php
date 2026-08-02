<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Http\Provider;

use Override;
use Sindri\Tests\Fixtures\Http\Controller\TestHttpControllerFixture;
use Valkyrja\Http\Routing\Provider\Contract\HttpRouteProviderContract;

final class TestRouteProviderFixture implements HttpRouteProviderContract
{
    #[Override]
    public static function getControllerClasses(): array
    {
        return [TestHttpControllerFixture::class];
    }

    #[Override]
    public static function getRoutes(): array
    {
        return [];
    }
}
