<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * (c) Melech Mizrachi <melechmizrachi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
