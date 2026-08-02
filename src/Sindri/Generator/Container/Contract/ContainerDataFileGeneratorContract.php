<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Generator\Container\Contract;

use Sindri\Generator\Enum\GenerateStatus;

interface ContainerDataFileGeneratorContract
{
    /**
     * Generate the container data file.
     *
     * @param non-empty-string                                       $directory
     * @param non-empty-string                                       $className
     * @param non-empty-string                                       $namespace
     * @param array<class-string, array{0: class-string, 1: string}> $publishers
     */
    public function generateFile(
        string $directory,
        string $className,
        string $namespace,
        array $publishers,
    ): GenerateStatus;

    /**
     * Generate the data class contents for inline use.
     *
     * @param array<class-string, array{0: class-string, 1: string}> $publishers
     *
     * @return non-empty-string
     */
    public function generateClassContents(array $publishers): string;
}
