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

namespace Sindri\Tests\Fixtures\Config\Provider;

use Override;
use Valkyrja\Application\Kernel\Contract\ApplicationContract;
use Valkyrja\Application\Provider\Contract\ComponentProviderContract;

final class TestComponentProviderFixture implements ComponentProviderContract
{
    #[Override]
    public function getComponentProviders(ApplicationContract $app): array
    {
        return [];
    }

    #[Override]
    public function getContainerProviders(ApplicationContract $app): array
    {
        return [];
    }

    #[Override]
    public function getEventProviders(ApplicationContract $app): array
    {
        return [];
    }

    #[Override]
    public function getCliProviders(ApplicationContract $app): array
    {
        return [];
    }

    #[Override]
    public function getHttpProviders(ApplicationContract $app): array
    {
        return [];
    }
}
