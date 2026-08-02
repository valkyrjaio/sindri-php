<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Event;

use Valkyrja\Event\Attribute\Listener;

#[Listener(eventId: TestEventFixture::class, name: 'test-class-listener')]
final class TestListenerFixture
{
    #[Listener(eventId: TestEventFixture::class, name: 'test-method-listener')]
    public function handle(): void
    {
    }
}
