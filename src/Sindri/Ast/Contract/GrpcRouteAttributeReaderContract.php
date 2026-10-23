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

use PhpParser\Error;
use Sindri\Ast\Data\Result\GrpcRouteAttributeResult;
use Sindri\Ast\Throwable\Exception\AstFileReadException;
use Sindri\Ast\Throwable\Exception\AstNoRouteHandlerException;

interface GrpcRouteAttributeReaderContract
{
    /**
     * Scan a gRPC service controller class source file and return all extracted route data objects.
     *
     * @throws Error
     * @throws AstFileReadException
     * @throws AstNoRouteHandlerException
     */
    public function readFile(string $filePath): GrpcRouteAttributeResult;
}
