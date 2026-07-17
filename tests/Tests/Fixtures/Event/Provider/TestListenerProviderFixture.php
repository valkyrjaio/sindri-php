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

namespace Sindri\Tests\Fixtures\Event\Provider;

use Override;
use Sindri\Tests\Fixtures\Event\TestListenerFixture;
use Valkyrja\Event\Provider\Contract\ListenerProviderContract;

final class TestListenerProviderFixture implements ListenerProviderContract
{
    #[Override]
    public static function getListenerClasses(): array
    {
        return [TestListenerFixture::class];
    }

    #[Override]
    public static function getListeners(): array
    {
        return [];
    }
}
