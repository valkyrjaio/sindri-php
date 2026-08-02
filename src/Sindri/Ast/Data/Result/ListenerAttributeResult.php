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

use PhpParser\Node\Expr;

/**
 * Result of scanning a listener class file for #[Listener] / #[ListenerHandler] attributes.
 *
 * Each element of $listeners is a PHP-Parser Expr node (typically Expr\New_)
 * ready to be embedded verbatim in the data cache file by the generator.
 */
readonly class ListenerAttributeResult
{
    /**
     * @param array<string, Expr> $listeners Listener name → AST expression (keyed for direct use in generated arrays)
     */
    public function __construct(
        public array $listeners = [],
    ) {
    }
}
