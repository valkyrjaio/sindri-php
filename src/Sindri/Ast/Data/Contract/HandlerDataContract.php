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
 * Contract for a portable callable representation stored as class + method pair.
 */
interface HandlerDataContract
{
    /** @var class-string */
    public string $class {
        get;
    }

    public string $method {
        get;
    }
}
