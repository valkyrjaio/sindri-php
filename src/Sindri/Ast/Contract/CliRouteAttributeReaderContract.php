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

use Sindri\Ast\Data\Result\CliRouteAttributeResult;

/**
 * Reads #[Route] and related CLI routing attributes from a controller class file.
 */
interface CliRouteAttributeReaderContract
{
    /**
     * Scan a CLI controller class source file and return all extracted route data objects.
     */
    public function readFile(string $filePath): CliRouteAttributeResult;
}
