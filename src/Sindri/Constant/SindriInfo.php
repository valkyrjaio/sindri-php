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

namespace Sindri\Constant;

final class SindriInfo
{
    /**
     * The Sindri package version.
     *
     * @var non-empty-string
     */
    public const string VERSION = '26.3.0';

    /**
     * The Sindri package version build datetime.
     *
     * @var non-empty-string
     */
    public const string VERSION_BUILD_DATE_TIME = 'May 16 2026 15:46:42 MST';

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
