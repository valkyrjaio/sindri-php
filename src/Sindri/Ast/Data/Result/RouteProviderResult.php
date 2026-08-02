<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Ast\Data\Result;

use PhpParser\Node\Expr;

use function array_unique;
use function array_values;

/**
 * Routes extracted from a single CliRouteProviderContract or HttpRouteProviderContract implementation.
 *
 * `controllerClasses` — classes listed by getControllerClasses() that carry #[Route]
 *                        attributes; a subsequent CliRouteAttributeReader or
 *                        HttpRouteAttributeReader will scan each class.
 *
 * `routes`             — raw AST Expr nodes captured verbatim from getRoutes();
 *                        the file generator writes them back out as-is so the exact
 *                        user-defined shape is preserved without re-interpretation.
 *
 * This result is shared by both CLI and HTTP route providers since their contracts
 * are structurally identical (getControllerClasses / getRoutes).
 */
readonly class RouteProviderResult
{
    /**
     * @param class-string[] $controllerClasses
     * @param Expr[]         $routes            Raw AST expressions from getRoutes()
     */
    public function __construct(
        public array $controllerClasses = [],
        public array $routes = [],
    ) {
    }

    /**
     * Merge another result into this one, deduplicating the controller class list.
     */
    public function merge(self $other): self
    {
        return new self(
            controllerClasses: array_values(array_unique([...$this->controllerClasses, ...$other->controllerClasses])),
            routes: [...$this->routes, ...$other->routes],
        );
    }
}
