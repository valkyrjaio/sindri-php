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
 * A listener provider that references a non-existent listener class.
 * Used to exercise the "listener class file not found" branch in GenerateDataFromAst.
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
