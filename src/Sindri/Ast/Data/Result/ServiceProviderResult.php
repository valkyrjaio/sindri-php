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

use function array_unique;
use function array_values;

/**
 * Services extracted from a single ServiceProviderContract implementation.
 *
 * `serviceClasses` — the keys of `publishers()` (service IDs).
 * `publishers`     — the full map: serviceId → [providerClass, methodName].
 */
readonly class ServiceProviderResult
{
    /**
     * @param class-string[]                                         $serviceClasses
     * @param array<class-string, array{0: class-string, 1: string}> $publishers
     */
    public function __construct(
        public array $serviceClasses = [],
        public array $publishers = [],
    ) {
    }

    /**
     * Merge another result into this one.
     */
    public function merge(self $other): self
    {
        return new self(
            serviceClasses: array_values(array_unique([...$this->serviceClasses, ...$other->serviceClasses])),
            publishers: [...$this->publishers, ...$other->publishers],
        );
    }
}
