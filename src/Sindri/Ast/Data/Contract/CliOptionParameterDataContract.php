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

interface CliOptionParameterDataContract
{
    public string $name {
        get;
    }

    public string $description {
        get;
    }

    public string $valueDisplayName {
        get;
    }

    public string|null $cast {
        get;
    }

    public string $defaultValue {
        get;
    }

    /** @var string[] */
    public array $shortNames {
        get;
    }

    /** @var string[] */
    public array $validValues {
        get;
    }

    /** Stored as "FQN::CASE" */
    public string $mode {
        get;
    }

    /** Stored as "FQN::CASE" */
    public string $valueMode {
        get;
    }
}
