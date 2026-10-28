<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Fixtures\Queue\Controller;

use Sindri\Tests\Fixtures\Queue\Middleware\TestQueueMiddlewareFixture;
use Sindri\Tests\Fixtures\Queue\Middleware\TestSingleStageQueueMiddlewareFixture;
use Valkyrja\Container\Manager\Contract\ContainerContract;
use Valkyrja\Queue\Message\Enum\JobResult;
use Valkyrja\Queue\Routing\Attribute\Route;
use Valkyrja\Queue\Routing\Attribute\Route\Handler;
use Valkyrja\Queue\Routing\Attribute\Route\Middleware;
use Valkyrja\Queue\Routing\Attribute\Route\Name;
use Valkyrja\Queue\Routing\Data\Contract\RouteContract;

final class TestRichQueueControllerFixture
{
    public static function handle(ContainerContract $container, RouteContract $route): JobResult
    {
        return JobResult::ACK;
    }

    #[Route(name: 'ChargeCard', description: 'Charge the card')]
    #[Name('ChargeCard.v2')]
    #[Handler([self::class, 'handle'])]
    #[Middleware(TestQueueMiddlewareFixture::class)]
    // An empty name is skipped rather than classified, so the reader must
    // tolerate one without emitting an unusable middleware entry
    /** @phpstan-ignore-next-line */
    #[Middleware('')]
    public function chargeCard(): JobResult
    {
        return JobResult::ACK;
    }

    #[Route(name: '', description: 'Missing a name so it is skipped')]
    public function unnamed(): JobResult
    {
        return JobResult::ACK;
    }

    // A route is keyed by both, so an empty description is skipped for the
    // same reason an empty name is
    #[Route(name: 'Undescribed', description: '')]
    public function undescribed(): JobResult
    {
        return JobResult::ACK;
    }

    // An override that carries nothing leaves the name the route declared
    #[Route(name: 'KeptName', description: 'The name attribute overrides nothing')]
    /** @phpstan-ignore-next-line */
    #[Name('')]
    public function keptName(): JobResult
    {
        return JobResult::ACK;
    }

    // One stage and no other, so every list but that one stays empty
    #[Route(name: 'SingleStage', description: 'Carries one middleware stage')]
    #[Middleware(TestSingleStageQueueMiddlewareFixture::class)]
    public function singleStage(): JobResult
    {
        return JobResult::ACK;
    }

    // The reader works off the syntax tree and never instantiates an
    // attribute, so an argument the signature forbids still reaches the guard
    #[Route(name: 'NonStringName', description: 'The name override is not a string')]
    /** @phpstan-ignore-next-line */
    #[Name(42)]
    public function nonStringName(): JobResult
    {
        return JobResult::ACK;
    }

    #[Route(name: 'NonStringMiddleware', description: 'The middleware name is not a string')]
    /** @phpstan-ignore-next-line */
    #[Middleware(42)]
    public function nonStringMiddleware(): JobResult
    {
        return JobResult::ACK;
    }
}
