<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Generator\Ast\Cli;

use PhpParser\Node\Scalar\String_;
use Sindri\Generator\Ast\Cli\AstCliDataFileGenerator;
use Sindri\Generator\Enum\GenerateStatus;
use Sindri\Tests\Unit\Abstract\TestCase;

use function sys_get_temp_dir;
use function unlink;

final class AstCliDataFileGeneratorTest extends TestCase
{
    public function testGenerateClassContentsWithEmptyRoutesContainsDataClass(): void
    {
        $generator = new AstCliDataFileGenerator();
        $contents  = $generator->generateClassContents([]);

        self::assertStringContainsString('Sindri\Cli\Data\CliRoutingData', $contents);
    }

    public function testGenerateClassContentsWithEmptyRoutesContainsEmptyRoutesArray(): void
    {
        $generator = new AstCliDataFileGenerator();
        $contents  = $generator->generateClassContents([]);

        self::assertStringContainsString('routes:', $contents);
    }

    public function testGenerateClassContentsWithRouteContainsRouteKey(): void
    {
        $generator = new AstCliDataFileGenerator();
        $contents  = $generator->generateClassContents([
            'test:route' => new String_('test-route-expr'),
        ]);

        self::assertStringContainsString("'test:route'", $contents);
    }

    public function testGenerateClassContentsWithConstantKeyOutputsConstantReference(): void
    {
        $generator = new AstCliDataFileGenerator();
        $contents  = $generator->generateClassContents([
            'Valkyrja\\Cli\\Server\\Constant\\CommandName::HELP' => new String_('help-expr'),
        ]);

        self::assertStringContainsString('\\Valkyrja\\Cli\\Server\\Constant\\CommandName::HELP', $contents);
        self::assertStringNotContainsString("'Valkyrja\\Cli\\Server\\Constant\\CommandName::HELP'", $contents);
    }

    public function testGenerateFileReturnsSuccessOnNewFile(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppCliRoutingDataTest' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $generator = new AstCliDataFileGenerator();
        $status    = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: [],
        );

        @unlink($filePath);

        self::assertSame(GenerateStatus::SUCCESS, $status);
    }

    public function testGenerateFileReturnsSkippedOnSameContent(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppCliRoutingDataSkip' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $generator = new AstCliDataFileGenerator();
        $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: [],
        );

        $status = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            routes: [],
        );

        @unlink($filePath);

        self::assertSame(GenerateStatus::SKIPPED, $status);
    }
}
