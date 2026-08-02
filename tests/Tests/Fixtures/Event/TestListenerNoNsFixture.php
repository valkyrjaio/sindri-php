<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

// phpcs:ignoreFile

use Sindri\Tests\Fixtures\Event\TestEventFixture;
use Valkyrja\Event\Attribute\Listener;

#[Listener(eventId: TestEventFixture::class, name: 'no-ns-class-listener')]
class TestListenerNoNsFixture
{
    #[Listener(eventId: TestEventFixture::class, name: 'no-ns-method-listener')]
    public function handle(): void
    {
    }
}
