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

namespace Sindri\Tests\Unit\Ast;

use Sindri\Ast\RouteProviderReader;
use Sindri\Tests\Fixtures\Cli\Controller\TestCliControllerClass;
use Sindri\Tests\Unit\Abstract\TestCase;

use function file_put_contents;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class RouteProviderReaderTest extends TestCase
{
    /** Fixture using ::class syntax — produces a real FQN in controllerClasses. */
    private static string $classFixtureFile;

    /** Legacy fixture using a plain string — controllerClasses comes back empty. */
    private static string $stringFixtureFile;

    public static function setUpBeforeClass(): void
    {
        /** @var non-empty-string $path */
        $path = realpath(__DIR__ . '/../../Fixtures/Cli/Provider/TestRouteProviderClass.php');

        self::$classFixtureFile = $path;

        /** @var non-empty-string $path */
        $path = realpath(__DIR__ . '/../../Fixtures/Cli/Provider/RouteProviderClass.php');

        self::$stringFixtureFile = $path;
    }

    public function testReadFileExtractsControllerClasses(): void
    {
        $result = new RouteProviderReader()->readFile(self::$classFixtureFile);

        self::assertSame([TestCliControllerClass::class], $result->controllerClasses);
    }

    public function testReadFileExtractsEmptyControllerClassesForPlainStringFixture(): void
    {
        // 'AControllerClass' is a plain string, not a ::class constant fetch, so
        // extractClassListFromValues returns an empty array for this fixture.
        $result = new RouteProviderReader()->readFile(self::$stringFixtureFile);

        self::assertSame([], $result->controllerClasses);
    }

    public function testReadFileExtractsEmptyRoutes(): void
    {
        $result = new RouteProviderReader()->readFile(self::$classFixtureFile);

        self::assertSame([], $result->routes);
    }

    public function testReadFileReturnsEmptyResultForFileWithNoClass(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sindri_test') . '.php';
        file_put_contents($tmpFile, "<?php\ndeclare(strict_types=1);\nnamespace Test;\n");

        $result = new RouteProviderReader()->readFile($tmpFile);

        @unlink($tmpFile);

        self::assertSame([], $result->controllerClasses);
        self::assertSame([], $result->routes);
    }
}
