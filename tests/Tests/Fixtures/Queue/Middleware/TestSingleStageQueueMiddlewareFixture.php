<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Queue\Middleware;

use Override;
use Valkyrja\Queue\Message\Enum\JobResult;
use Valkyrja\Queue\Message\Job\Contract\JobContract;
use Valkyrja\Queue\Middleware\Contract\RouteMatchedMiddlewareContract;
use Valkyrja\Queue\Middleware\Handler\Contract\RouteMatchedHandlerContract;
use Valkyrja\Queue\Routing\Data\Contract\RouteContract;

/**
 * Serves one queue middleware stage and no other, so the reader must leave it
 * out of every list but the one it belongs to.
 */
final class TestSingleStageQueueMiddlewareFixture implements RouteMatchedMiddlewareContract
{
    #[Override]
    public function routeMatched(JobContract $job, RouteContract $route, RouteMatchedHandlerContract $handler): RouteContract|JobResult
    {
        return $handler->routeMatched($job, $route);
    }
}
