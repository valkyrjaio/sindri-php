<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Provider\Sub;

use Override;
use Valkyrja\Container\Manager\Contract\ContainerContract;
use Valkyrja\Container\Provider\Contract\ServiceProviderContract;

final class TestServiceProviderFixture implements ServiceProviderContract
{
    #[Override]
    public static function publishers(): array
    {
        return [
            TestServiceFixture::class      => [self::class, 'publishTestService'],
            TestOtherServiceFixture::class => [TestOtherServiceProviderFixture::class, 'publishTestOtherService'],
        ];
    }

    public static function publishTestService(ContainerContract $container): void
    {
    }
}
