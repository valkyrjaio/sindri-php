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

use Sindri\Ast\Data\Result\ListenerAttributeResult;

/**
 * Reads #[Listener] and #[ListenerHandler] attributes from a listener class file.
 */
interface ListenerAttributeReaderContract
{
    /**
     * Scan a listener class source file and return all extracted listener data objects.
     */
    public function readFile(string $filePath): ListenerAttributeResult;
}
