<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Config;

use Sindri\Tests\Fixtures\Config\Provider\TestComponentProviderFixture;
use Valkyrja\Application\Data\CliConfig;

final class TestConfigFixture extends CliConfig
{
    public function __construct()
    {
        parent::__construct(
            namespace: 'Sindri\\Tests\\Fixtures',
            dir: __DIR__ . '/../..',
            dataPath: 'Fixtures/Config/Data',
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Config\\Data',
            providers: [new TestComponentProviderFixture()],
        );
    }
}
