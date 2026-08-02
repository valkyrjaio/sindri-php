<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Generator\Ast\Container;

use Sindri\Generator\Ast\Container\AstContainerDataFileGenerator;
use Sindri\Generator\Enum\GenerateStatus;
use Sindri\Tests\Unit\Abstract\TestCase;

use function sys_get_temp_dir;
use function unlink;

final class AstContainerDataFileGeneratorTest extends TestCase
{
    public function testGenerateClassContentsWithEmptyPublishersContainsDataClass(): void
    {
        $generator = new AstContainerDataFileGenerator();
        $contents  = $generator->generateClassContents([]);

        self::assertStringContainsString('ContainerData', $contents);
    }

    public function testGenerateClassContentsWithEmptyPublishersContainsCallbacks(): void
    {
        $generator = new AstContainerDataFileGenerator();
        $contents  = $generator->generateClassContents([]);

        self::assertStringContainsString('callbacks:', $contents);
    }

    public function testGenerateClassContentsWithPublishersContainsPublisherEntry(): void
    {
        $generator = new AstContainerDataFileGenerator();
        $contents  = $generator->generateClassContents([
            'SomeService' => ['SomeProvider', 'publishSome'],
        ]);

        self::assertStringContainsString('SomeService', $contents);
        self::assertStringContainsString('SomeProvider', $contents);
        self::assertStringContainsString('publishSome', $contents);
    }

    public function testGenerateFileReturnsSuccessOnNewFile(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppContainerDataTest' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $generator = new AstContainerDataFileGenerator();
        $status    = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            publishers: [],
        );

        @unlink($filePath);

        self::assertSame(GenerateStatus::SUCCESS, $status);
    }

    public function testGenerateFileReturnsSkippedOnSameContent(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppContainerDataSkip' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $generator = new AstContainerDataFileGenerator();
        $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            publishers: [],
        );

        $status = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            publishers: [],
        );

        @unlink($filePath);

        self::assertSame(GenerateStatus::SKIPPED, $status);
    }
}
