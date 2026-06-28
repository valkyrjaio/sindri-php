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

namespace Sindri\Tests\Fixtures\Provider;

use Override;
use Sindri\Tests\Fixtures\Provider\Sub\TestServiceProviderClass;
use Sindri\Tests\Fixtures\Provider\Sub\TestSubComponentProviderClass;
use Valkyrja\Application\Kernel\Contract\ApplicationContract;
use Valkyrja\Application\Provider\Contract\ComponentProviderContract;

final class TestComponentProviderClass implements ComponentProviderContract
{
    #[Override]
    public function getComponentProviders(ApplicationContract $app): array
    {
        return [new TestSubComponentProviderClass()];
    }

    #[Override]
    public function getContainerProviders(ApplicationContract $app): array
    {
        return [new TestServiceProviderClass()];
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
