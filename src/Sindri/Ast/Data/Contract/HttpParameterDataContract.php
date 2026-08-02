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
 * Contract for a portable HTTP route parameter intermediate representation.
 */
interface HttpParameterDataContract
{
    public string $name {
        get;
    }

    public string $regex {
        get;
    }

    /** Stored as "FQN::CASE" or null */
    public string|null $cast {
        get;
    }

    public bool $isOptional {
        get;
    }

    public bool $shouldCapture {
        get;
    }
}
