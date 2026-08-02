<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Throwable\Exception\Abstract;

use Sindri\Throwable\Contract\SindriThrowable;
use Valkyrja\Throwable\Exception\Abstract\ValkyrjaRuntimeException;

abstract class SindriRuntimeException extends ValkyrjaRuntimeException implements SindriThrowable
{
}
