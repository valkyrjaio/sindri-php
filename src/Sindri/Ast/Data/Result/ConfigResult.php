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

/**
 * Config values extracted from an application config class file via AST.
 */
readonly class ConfigResult
{
    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $dir
     * @param non-empty-string $dataPath
     * @param non-empty-string $dataNamespace
     * @param class-string[]   $providers
     */
    public function __construct(
        public string $namespace = '',
        public string $dir = '',
        public string $dataPath = '',
        public string $dataNamespace = '',
        public array $providers = [],
    ) {
    }
}
