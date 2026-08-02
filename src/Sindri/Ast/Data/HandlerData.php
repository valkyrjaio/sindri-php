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

use Sindri\Ast\Data\Contract\HandlerDataContract;

/**
 * Represents a PHP callable stored as a two-element array: [ClassName::class, 'methodName'].
 * This is the portable intermediate form used before generating the PHP output file.
 */
readonly class HandlerData implements HandlerDataContract
{
    /**
     * @param class-string $class  Fully-qualified class name
     * @param string       $method Method name
     */
    public function __construct(
        public string $class,
        public string $method,
    ) {
    }
}
