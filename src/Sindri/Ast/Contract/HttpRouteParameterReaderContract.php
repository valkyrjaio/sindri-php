<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Ast\Contract;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\ClassMethod;
use Sindri\Ast\Data\HttpParameterData;

/**
 * Reads and builds AST expressions for HTTP dynamic route parameters.
 */
interface HttpRouteParameterReaderContract
{
    /**
     * Collect parameters from inline DynamicRoute args and #[Parameter] attributes.
     *
     * @param Arg[]                 $args
     * @param array<string, string> $useMap
     *
     * @return HttpParameterData[]
     */
    public function updateParameters(
        array $args,
        ClassMethod $method,
        array $useMap,
        string $namespace,
        string $currentClass,
    ): array;

    /**
     * Build an array expression of Parameter New_ nodes.
     *
     * @param HttpParameterData[] $parameters
     */
    public function buildParameterListExpr(array $parameters): Array_;
}
