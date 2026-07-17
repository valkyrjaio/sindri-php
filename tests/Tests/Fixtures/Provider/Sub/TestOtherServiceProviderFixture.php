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

namespace Sindri\Tests\Fixtures\Provider\Sub;

use Override;
use Valkyrja\Container\Manager\Contract\ContainerContract;
use Valkyrja\Container\Provider\Contract\ServiceProviderContract;

final class TestOtherServiceProviderFixture implements ServiceProviderContract
{
    #[Override]
    public static function publishers(): array
    {
        return [
            TestOtherServiceFixture::class => [self::class, 'publishTestOtherService'],
        ];
    }

    public static function publishTestOtherService(ContainerContract $container): void
    {
    }
}
