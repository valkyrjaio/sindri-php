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
 * Contract for a portable application config intermediate representation.
 */
interface ConfigDataContract
{
    public string $namespace {
        get;
    }

    public string $dir {
        get;
    }

    public string $dataPath {
        get;
    }

    public string $dataNamespace {
        get;
    }

    /** @var class-string[] */
    public array $providers {
        get;
    }
}
