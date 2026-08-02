<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Cli\Controller;

use Valkyrja\Cli\Routing\Attribute\ArgumentParameter;
use Valkyrja\Cli\Routing\Attribute\OptionParameter;
use Valkyrja\Cli\Routing\Attribute\Route;

final class TestCliControllerFixture
{
    #[Route(name: 'test:cli-route', description: 'A test CLI route')]
    #[ArgumentParameter(name: 'arg1', description: 'First argument')]
    #[OptionParameter(name: 'opt1', description: 'First option', shortNames: ['o'])]
    public function testAction(): void
    {
    }
}
