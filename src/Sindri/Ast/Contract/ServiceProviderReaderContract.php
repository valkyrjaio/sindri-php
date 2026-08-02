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

use Sindri\Ast\Data\Result\ServiceProviderResult;

interface ServiceProviderReaderContract
{
    /**
     * Read the services published by parsing the given ServiceProviderContract source file.
     *
     * Extracts the keys of the `publishers()` return array as fully-qualified
     * service class names.
     *
     * @param non-empty-string $filePath Absolute path to the PHP source file
     */
    public function readFile(string $filePath): ServiceProviderResult;
}
