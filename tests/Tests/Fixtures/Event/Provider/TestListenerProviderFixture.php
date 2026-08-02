<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
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
