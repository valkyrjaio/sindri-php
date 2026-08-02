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
use Sindri\Ast\Data\HttpRouteData;

/**
 * Result of scanning an HTTP controller class file for #[Route] / #[DynamicRoute] and related attributes.
 *
 * Each element of $routes is a PHP-Parser Expr node ready to be embedded in the data cache file.
 * Each element of $routeData is the plain HttpRouteData used to build paths/dynamicPaths/regexes.
 */
readonly class HttpRouteAttributeResult
{
    /**
     * @param array<string, Expr>          $routes    Route name → AST expression
     * @param array<string, HttpRouteData> $routeData Route name → plain route data
     */
    public function __construct(
        public array $routes = [],
        public array $routeData = [],
    ) {
    }
}
