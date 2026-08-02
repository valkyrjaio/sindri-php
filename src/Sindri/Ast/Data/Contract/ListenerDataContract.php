<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Ast\Data\Contract;

/**
 * Contract for a portable listener intermediate representation.
 */
interface ListenerDataContract
{
    /** @var class-string */
    public string $eventId {
        get;
    }

    public string $name {
        get;
    }

    public HandlerDataContract|null $handler {
        get;
    }
}
