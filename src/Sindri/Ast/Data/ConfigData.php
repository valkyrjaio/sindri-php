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

use Sindri\Ast\Data\Contract\ConfigDataContract;

/**
 * Portable intermediate representation of application config extracted from
 * a PHP config file via AST (e.g. new Config(...) constructor arguments).
 *
 * Mirrors the shape of Valkyrja\Application\Data\Config without requiring
 * the framework class to be instantiated.
 */
readonly class ConfigData implements ConfigDataContract
{
    /**
     * @param string         $namespace     Application namespace prefix (e.g. "App")
     * @param string         $dir           Application root directory (absolute path)
     * @param string         $dataPath      Relative path where generated data files are written
     * @param string         $dataNamespace PHP namespace for generated data classes
     * @param class-string[] $providers     Top-level component provider FQNs
     */
    public function __construct(
        public string $namespace,
        public string $dir,
        public string $dataPath,
        public string $dataNamespace,
        public array $providers = [],
    ) {
    }
}
