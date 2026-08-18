<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Constant;

final class SindriInfo
{
    /**
     * The Sindri package version.
     *
     * @var non-empty-string
     */
    public const string VERSION = '26.6.18';

    /**
     * The Sindri package version build datetime.
     *
     * @var non-empty-string
     */
    public const string VERSION_BUILD_DATE_TIME = 'August 18 2026 06:42:14 MST';

    /**
     * The CLI banner icon (Mjölnir).
     *
     * @var non-empty-string
     */
    public const string ICON = <<<'ICON'
        ▗▄█████▄▖
        ▝▀█████▀▘
            █
            █
        ICON;
}
