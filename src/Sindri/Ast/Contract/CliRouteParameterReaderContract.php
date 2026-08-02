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
use PhpParser\Node\Stmt\ClassMethod;
use Sindri\Ast\Data\CliArgumentParameterData;
use Sindri\Ast\Data\CliOptionParameterData;
use Sindri\Ast\Data\CliRouteData;

/**
 * Reads and builds AST expressions for CLI route argument and option parameters.
 */
interface CliRouteParameterReaderContract
{
    /**
     * Build the arguments/options named-arg list for a CliRouteData.
     *
     * @return Arg[]
     */
    public function buildParameterArgs(CliRouteData $data): array;

    /**
     * Collect all #[ArgumentParameter] attributes from the method.
     *
     * @param array<string, string> $useMap
     *
     * @return CliArgumentParameterData[]
     */
    public function updateArguments(
        ClassMethod $method,
        array $useMap,
        string $namespace,
        string $currentClass,
    ): array;

    /**
     * Collect all #[OptionParameter] attributes from the method.
     *
     * @param array<string, string> $useMap
     *
     * @return CliOptionParameterData[]
     */
    public function updateOptions(
        ClassMethod $method,
        array $useMap,
        string $namespace,
        string $currentClass,
    ): array;
}
