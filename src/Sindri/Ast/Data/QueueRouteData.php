<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Ast\Data;

use Sindri\Ast\Data\Contract\QueueRouteDataContract;

/**
 * Portable intermediate representation of a single queue route extracted from
 * #[Route] and related sub-attributes on a job handler method.
 *
 * Mirrors the shape of Valkyrja\Queue\Routing\Data\Route without requiring the
 * framework data class to be instantiated. A queue route carries no retry or
 * attempts policy — those ride on the job, because the producer decides them.
 *
 * Middleware class names are stored as FQN strings.
 */
readonly class QueueRouteData implements QueueRouteDataContract
{
    /**
     * @param string           $name                      Job name — the router map key
     * @param string           $description               Route description
     * @param HandlerData|null $handler                   Resolved handler (from #[RouteHandler])
     * @param class-string[]   $routeMatchedMiddleware
     * @param class-string[]   $routeDispatchedMiddleware
     * @param class-string[]   $throwableCaughtMiddleware
     * @param class-string[]   $settlingResultMiddleware
     * @param class-string[]   $resultSettledMiddleware
     */
    public function __construct(
        public string $name,
        public string $description,
        public HandlerData|null $handler = null,
        public array $routeMatchedMiddleware = [],
        public array $routeDispatchedMiddleware = [],
        public array $throwableCaughtMiddleware = [],
        public array $settlingResultMiddleware = [],
        public array $resultSettledMiddleware = [],
    ) {
    }
}
