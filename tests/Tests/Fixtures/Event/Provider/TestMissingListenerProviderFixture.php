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
use Valkyrja\Event\Provider\Contract\ListenerProviderContract;

/**
 * A listener provider naming a listener class that does not exist, for the not-found branch.
 */
final class TestMissingListenerProviderFixture implements ListenerProviderContract
{
    #[Override]
    public static function getListenerClasses(): array
    {
        /* @phpstan-ignore class.notFound */
        return [NonExistentListenerClass::class];
    }

    #[Override]
    public static function getListeners(): array
    {
        return [];
    }
}
