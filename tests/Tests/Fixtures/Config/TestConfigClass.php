<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * (c) Melech Mizrachi <melechmizrachi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sindri\Tests\Fixtures\Config;

use Sindri\Tests\Fixtures\Config\Provider\TestComponentProviderClass;
use Valkyrja\Application\Data\CliConfig;

final class TestConfigClass extends CliConfig
{
    public function __construct()
    {
        parent::__construct(
            namespace: 'Sindri\\Tests\\Fixtures',
            dir: __DIR__ . '/../..',
            dataPath: 'Fixtures/Config/Data',
            dataNamespace: 'Sindri\\Tests\\Fixtures\\Config\\Data',
            providers: [new TestComponentProviderClass()],
        );
    }
}
