<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Generator\Contract;

use Sindri\Generator\Enum\GenerateStatus;

interface FileGeneratorContract
{
    /**
     * Generate a file.
     */
    public function generateFile(): GenerateStatus;

    /**
     * Generate the file contents.
     *
     * @return non-empty-string
     */
    public function generateFileContents(): string;
}
