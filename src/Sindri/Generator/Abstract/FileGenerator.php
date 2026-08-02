<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Generator\Abstract;

use Override;
use Sindri\Generator\Contract\FileGeneratorContract;
use Sindri\Generator\Enum\GenerateStatus;
use Throwable;

use function file_get_contents;
use function file_put_contents;
use function is_file;
use function rtrim;

abstract class FileGenerator implements FileGeneratorContract
{
    protected string $filePath;

    /**
     * @param non-empty-string $directory The directory
     * @param non-empty-string $className The class name
     */
    public function __construct(
        protected string $directory,
        protected string $className,
    ) {
        $this->filePath = rtrim($directory, '/') . "/$className.php";
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function generateFile(): GenerateStatus
    {
        try {
            $data     = $this->generateFileContents();
            $existing = $this->fileGetContents();

            if ($existing === $data) {
                return GenerateStatus::SKIPPED;
            }

            $results = $this->filePutContents(data: $data);

            if ($results !== false) {
                return GenerateStatus::SUCCESS;
            }
        } catch (Throwable) {
            // Fallthrough
        }

        return GenerateStatus::FAILURE;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    abstract public function generateFileContents(): string;

    /**
     * Wrapper for the file_get_contents function.
     */
    protected function fileGetContents(): string|false
    {
        if (! is_file(filename: $this->filePath)) {
            return false;
        }

        return file_get_contents(filename: $this->filePath);
    }

    /**
     * Wrapper for the file_put_contents function.
     */
    protected function filePutContents(string $data): int|false
    {
        return file_put_contents(filename: $this->filePath, data: $data);
    }
}
