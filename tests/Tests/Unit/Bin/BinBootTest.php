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

namespace Sindri\Tests\Unit\Bin;

use Sindri\Tests\Unit\Abstract\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function implode;

use const PHP_BINARY;

/**
 * Smoke test that the `bin/sindri` entry point actually boots.
 *
 * Booting the CLI collects the component providers configured in `bin/sindri`,
 * which is where a regression is easy to introduce and invisible to the unit
 * tests — e.g. passing provider class-strings instead of instances fatals the
 * whole application before any command runs, yet every generator unit test still
 * passes. Running the real binary end-to-end catches that class of bug.
 */
final class BinBootTest extends TestCase
{
    public function testBinBootsAndListsCommands(): void
    {
        $binPath = dirname(__DIR__, 4) . '/bin/sindri';

        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($binPath) . ' list 2>&1';

        $output   = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        $stdout = implode("\n", $output);

        // A clean boot exits 0 and reaches the command listing.
        self::assertSame(0, $exitCode, "bin/sindri did not boot cleanly:\n" . $stdout);
        self::assertStringContainsString('data:generate', $stdout);
        // A provider mis-configuration surfaces as a TypeError during boot.
        self::assertStringNotContainsString('TypeError', $stdout);
        self::assertStringNotContainsString('Fatal error', $stdout);
    }
}
