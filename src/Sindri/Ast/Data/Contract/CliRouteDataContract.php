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

interface CliRouteDataContract
{
    public string $name {
        get;
    }

    public string $description {
        get;
    }

    public HandlerDataContract|null $handler {
        get;
    }

    public HandlerDataContract|null $helpText {
        get;
    }

    /** @var class-string[] */
    public array $routeMatchedMiddleware {
        get;
    }

    /** @var class-string[] */
    public array $routeDispatchedMiddleware {
        get;
    }

    /** @var class-string[] */
    public array $throwableCaughtMiddleware {
        get;
    }

    /** @var class-string[] */
    public array $processExitingMiddleware {
        get;
    }

    /** @var CliArgumentParameterDataContract[] */
    public array $arguments {
        get;
    }

    /** @var CliOptionParameterDataContract[] */
    public array $options {
        get;
    }
}
