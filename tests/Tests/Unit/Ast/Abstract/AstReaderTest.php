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

namespace Sindri\Tests\Unit\Ast\Abstract;

use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use Sindri\Ast\Abstract\AstReader;
use Sindri\Ast\Data\HandlerData;
use Sindri\Ast\Throwable\Exception\AstFileReadException;
use Sindri\Tests\Unit\Abstract\TestCase;

final class AstReaderTest extends TestCase
{
    private AstReader $reader;

    /** @var string[] */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->reader = new class extends AstReader {
            public function callParseFileToStmts(string $filePath): array
            {
                return $this->parseFileToStmts($filePath);
            }

            public function callExtractClassListFromKeys(ClassMethod|null $method, array $useMap, string $namespace): array
            {
                return $this->extractClassListFromKeys($method, $useMap, $namespace);
            }

            public function callExtractClassListFromValues(ClassMethod|null $method, array $useMap, string $namespace): array
            {
                return $this->extractClassListFromValues($method, $useMap, $namespace);
            }

            public function callExtractClassListFromArrayExpr(Array_ $array, array $useMap, string $namespace, string $currentClass = ''): array
            {
                return $this->extractClassListFromArrayExpr($array, $useMap, $namespace, $currentClass);
            }

            public function callExtractExprValue(mixed $expr, array $useMap, string $namespace, string $currentClass = ''): mixed
            {
                return $this->extractExprValue($expr, $useMap, $namespace, $currentClass);
            }

            public function callExtractHandlerFromArray(Array_ $array, array $useMap, string $namespace, string $currentClass = ''): HandlerData|null
            {
                return $this->extractHandlerFromArray($array, $useMap, $namespace, $currentClass);
            }

            public function callResolveClassName(string $shortName, array $useMap, string $namespace): string
            {
                return $this->resolveClassName($shortName, $useMap, $namespace);
            }

            public function callNameToFqn(Name $name, array $useMap, string $namespace): string
            {
                return $this->nameToFqn($name, $useMap, $namespace);
            }

            public function callBuildEnumCaseExpr(string $fqnColonCase): Expr
            {
                return $this->buildEnumCaseExpr($fqnColonCase);
            }

            public function callNewExprToFqn(mixed $expr, array $useMap, string $namespace): string|null
            {
                return $this->newExprToFqn($expr, $useMap, $namespace);
            }

            /** @param array<int, mixed> $stmts @return array{string, array<int, mixed>} */
            public function callUnwrapNamespace(array $stmts): array
            {
                return $this->unwrapNamespace($stmts);
            }

            /** @param array<int, mixed> $stmts @return array<string, string> */
            public function callBuildUseMap(array $stmts): array
            {
                return $this->buildUseMap($stmts);
            }

            /** @param array<int, mixed> $stmts */
            public function callFindClass(array $stmts): Class_|null
            {
                return $this->findClass($stmts);
            }

            /** @return array<string, ClassMethod> */
            public function callIndexMethods(Class_ $class): array
            {
                return $this->indexMethods($class);
            }

            /** @return Attribute[] */
            public function callFindAttributesOnNode(Class_|ClassMethod|Param $node, string $fqn, array $useMap, string $namespace): array
            {
                return $this->findAttributesOnNode($node, $fqn, $useMap, $namespace);
            }

            /** @param Arg[] $args */
            public function callGetAttrArg(array $args, string $name, int $position = 0): mixed
            {
                return $this->getAttrArg($args, $name, $position);
            }

            /** @return array{0: Class_, 1: string, 2: array<string, string>, 3: string}|null */
            public function callParseClassFile(string $filePath): array|null
            {
                return $this->parseClassFile($filePath);
            }

            /** @param Arg[] $args */
            public function callExtractStringArg(array $args, string $name, int $position, array $useMap, string $namespace, string $currentClass, string $default = ''): string
            {
                return $this->extractStringArg($args, $name, $position, $useMap, $namespace, $currentClass, $default);
            }

            /** @param Arg[] $args */
            public function callExtractBoolArg(array $args, string $name, int $position, array $useMap, string $namespace, string $currentClass, bool $default = false): bool
            {
                return $this->extractBoolArg($args, $name, $position, $useMap, $namespace, $currentClass, $default);
            }

            /** @param Arg[] $args @return string[] */
            public function callExtractClassListArg(array $args, string $name, int $position, array $useMap, string $namespace, string $currentClass): array
            {
                return $this->extractClassListArg($args, $name, $position, $useMap, $namespace, $currentClass);
            }

            /** @return string[] */
            public function callExtractStringListFromArrayExpr(Array_ $array, array $useMap, string $namespace, string $currentClass = ''): array
            {
                return $this->extractStringListFromArrayExpr($array, $useMap, $namespace, $currentClass);
            }

            /** @param Arg[] $args @return string[] */
            public function callExtractStringListArg(array $args, string $name, int $position, array $useMap, string $namespace, string $currentClass): array
            {
                return $this->extractStringListArg($args, $name, $position, $useMap, $namespace, $currentClass);
            }

            public function callBuildStringExpr(string $value): String_
            {
                return $this->buildStringExpr($value);
            }

            public function callBuildClassConstExpr(string $fqn): ClassConstFetch
            {
                return $this->buildClassConstExpr($fqn);
            }

            public function callBuildHandlerExpr(HandlerData $handler): Array_
            {
                return $this->buildHandlerExpr($handler);
            }

            /** @param string[] $classes */
            public function callBuildClassArrayExpr(array $classes): Array_
            {
                return $this->buildClassArrayExpr($classes);
            }

            public function callBuildNamedArg(string $name, Expr $value): Arg
            {
                return $this->buildNamedArg($name, $value);
            }

            /** @param Arg[] $args */
            public function callBuildNewExpr(string $fqn, array $args): New_
            {
                return $this->buildNewExpr($fqn, $args);
            }

            public function callBuildBoolExpr(bool $value): ConstFetch
            {
                return $this->buildBoolExpr($value);
            }

            /** @param string[] $values */
            public function callBuildStringArrayExpr(array $values): Array_
            {
                return $this->buildStringArrayExpr($values);
            }

            /** @param string[] $enumCases */
            public function callBuildEnumCaseArrayExpr(array $enumCases): Array_
            {
                return $this->buildEnumCaseArrayExpr($enumCases);
            }
        };
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->tempFiles = [];
    }

    // -------------------------------------------------------------------------
    // extractClassListFromKeys
    // -------------------------------------------------------------------------

    public function testExtractClassListFromKeysReturnsEmptyForNullMethod(): void
    {
        $result = $this->reader->callExtractClassListFromKeys(null, [], '');

        self::assertSame([], $result);
    }

    public function testExtractClassListFromKeysReturnsEmptyForMethodWithNoReturnArray(): void
    {
        $method        = new ClassMethod(new Identifier('test'));
        $method->stmts = [];

        $result = $this->reader->callExtractClassListFromKeys($method, [], 'App');

        self::assertSame([], $result);
    }

    public function testExtractClassListFromKeysExtractsClassConstFetchKeys(): void
    {
        $method  = new ClassMethod(new Identifier('publishers'));
        $keyNode = new ClassConstFetch(new FullyQualified('App\\Service'), new Identifier('class'));
        $item    = new ArrayItem(new String_('value'), $keyNode);
        $array   = new Array_([$item]);
        $return  = new Return_($array);

        $method->stmts = [$return];

        $result = $this->reader->callExtractClassListFromKeys($method, [], 'App');

        self::assertSame(['App\\Service'], $result);
    }

    public function testExtractClassListFromKeysSkipsNullItems(): void
    {
        $method = new ClassMethod(new Identifier('publishers'));
        $array  = new Array_([null]);
        $return = new Return_($array);

        $method->stmts = [$return];

        $result = $this->reader->callExtractClassListFromKeys($method, [], 'App');

        self::assertSame([], $result);
    }

    public function testExtractClassListFromKeysSkipsNonClassConstFetchKeys(): void
    {
        $method = new ClassMethod(new Identifier('publishers'));
        $item   = new ArrayItem(new String_('value'), new String_('string-key'));
        $array  = new Array_([$item]);
        $return = new Return_($array);

        $method->stmts = [$return];

        $result = $this->reader->callExtractClassListFromKeys($method, [], 'App');

        self::assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // extractClassListFromValues — null item guard
    // -------------------------------------------------------------------------

    public function testExtractClassListFromValuesSkipsNullItems(): void
    {
        $method = new ClassMethod(new Identifier('listClasses'));
        $array  = new Array_([null]);
        $return = new Return_($array);

        $method->stmts = [$return];

        $result = $this->reader->callExtractClassListFromValues($method, [], 'App');

        self::assertSame([], $result);
    }

    public function testExtractClassListFromValuesReturnsEmptyForNullMethod(): void
    {
        $result = $this->reader->callExtractClassListFromValues(null, [], '');

        self::assertSame([], $result);
    }

    public function testExtractClassListFromValuesReturnsEmptyForMethodWithNoReturnArray(): void
    {
        $method        = new ClassMethod(new Identifier('test'));
        $method->stmts = [];

        $result = $this->reader->callExtractClassListFromValues($method, [], 'App');

        self::assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // extractExprValue — Int_, Float_, UnaryMinus, InterpolatedString
    // -------------------------------------------------------------------------

    public function testExtractExprValueReturnsIntForIntNode(): void
    {
        $result = $this->reader->callExtractExprValue(new Int_(42), [], '');

        self::assertSame(42, $result);
    }

    public function testExtractExprValueReturnsFloatForFloatNode(): void
    {
        $result = $this->reader->callExtractExprValue(new Float_(3.14), [], '');

        self::assertSame(3.14, $result);
    }

    public function testExtractExprValueReturnsNegatedIntForUnaryMinusInt(): void
    {
        $result = $this->reader->callExtractExprValue(new UnaryMinus(new Int_(5)), [], '');

        self::assertSame(-5, $result);
    }

    public function testExtractExprValueReturnsNegatedFloatForUnaryMinusFloat(): void
    {
        $result = $this->reader->callExtractExprValue(new UnaryMinus(new Float_(1.5)), [], '');

        self::assertSame(-1.5, $result);
    }

    public function testExtractExprValueReturnsNullForUnaryMinusString(): void
    {
        $result = $this->reader->callExtractExprValue(new UnaryMinus(new String_('abc')), [], '');

        self::assertNull($result);
    }

    public function testExtractExprValueReturnsNullForInterpolatedString(): void
    {
        $result = $this->reader->callExtractExprValue(new InterpolatedString([]), [], '');

        self::assertNull($result);
    }

    // -------------------------------------------------------------------------
    // resolveClassName — prefix-alias branch
    // -------------------------------------------------------------------------

    public function testResolveClassNameResolvesViaExactAliasMatch(): void
    {
        $result = $this->reader->callResolveClassName('MyAlias', ['MyAlias' => 'Full\\Ns\\MyClass'], 'App');

        self::assertSame('Full\\Ns\\MyClass', $result);
    }

    public function testResolveClassNameResolvesViaPrefixAliasMatch(): void
    {
        $useMap = ['Routing' => 'Valkyrja\\Http\\Routing'];

        $result = $this->reader->callResolveClassName('Routing\\Attribute\\Route', $useMap, 'App');

        self::assertSame('Valkyrja\\Http\\Routing\\Attribute\\Route', $result);
    }

    public function testResolveClassNamePrependsNamespaceWhenNoAliasMatch(): void
    {
        $result = $this->reader->callResolveClassName('SomeClass', [], 'App\\Ns');

        self::assertSame('App\\Ns\\SomeClass', $result);
    }

    public function testResolveClassNameReturnsShortNameWhenNoNamespaceAndNoAlias(): void
    {
        $result = $this->reader->callResolveClassName('SomeClass', [], '');

        self::assertSame('SomeClass', $result);
    }

    // -------------------------------------------------------------------------
    // extractHandlerFromArray — edge cases
    // -------------------------------------------------------------------------

    public function testExtractHandlerFromArrayReturnsNullForNonTwoElementArray(): void
    {
        $array = new Array_([new ArrayItem(new String_('only-one'))]);

        $result = $this->reader->callExtractHandlerFromArray($array, [], '');

        self::assertNull($result);
    }

    public function testExtractHandlerFromArrayReturnsNullWhenFirstItemIsNull(): void
    {
        $array = new Array_([null, new ArrayItem(new String_('method'))]);

        $result = $this->reader->callExtractHandlerFromArray($array, [], '');

        self::assertNull($result);
    }

    public function testExtractHandlerFromArrayReturnsNullWhenSecondItemIsNull(): void
    {
        $classItem = new ArrayItem(
            new ClassConstFetch(new FullyQualified('App\\Controller'), new Identifier('class'))
        );
        $array = new Array_([$classItem, null]);

        $result = $this->reader->callExtractHandlerFromArray($array, [], '');

        self::assertNull($result);
    }

    public function testExtractHandlerFromArrayReturnsNullWhenClassItemIsNotClassConstFetch(): void
    {
        $array = new Array_([
            new ArrayItem(new String_('not-a-class')),
            new ArrayItem(new String_('method')),
        ]);

        $result = $this->reader->callExtractHandlerFromArray($array, [], '');

        self::assertNull($result);
    }

    public function testExtractHandlerFromArrayReturnsNullWhenMethodIsNotString(): void
    {
        $classItem  = new ArrayItem(new ClassConstFetch(new FullyQualified('App\\Cls'), new Identifier('class')));
        $methodItem = new ArrayItem(new Int_(42));
        $array      = new Array_([$classItem, $methodItem]);

        $result = $this->reader->callExtractHandlerFromArray($array, [], '');

        self::assertNull($result);
    }

    public function testExtractHandlerFromArrayReturnsHandlerDataForValidInput(): void
    {
        $classItem  = new ArrayItem(new ClassConstFetch(new FullyQualified('App\\Controller'), new Identifier('class')));
        $methodItem = new ArrayItem(new String_('handle'));
        $array      = new Array_([$classItem, $methodItem]);

        $result = $this->reader->callExtractHandlerFromArray($array, [], '');

        self::assertInstanceOf(HandlerData::class, $result);
        self::assertSame('App\\Controller', $result->class);
        self::assertSame('handle', $result->method);
    }

    // -------------------------------------------------------------------------
    // parseFileToStmts — unreadable file throws AstFileReadException
    // -------------------------------------------------------------------------

    public function testParseFileToStmtsThrowsForUnreadableFile(): void
    {
        $this->expectException(AstFileReadException::class);

        @$this->reader->callParseFileToStmts('/nonexistent/path/to/file.php');
    }

    // -------------------------------------------------------------------------
    // extractExprValue — ConstFetch(false) and ConstFetch(null)
    // -------------------------------------------------------------------------

    public function testExtractExprValueReturnsFalseForConstFetchFalse(): void
    {
        $result = $this->reader->callExtractExprValue(new ConstFetch(new Name('false')), [], '');

        self::assertFalse($result);
    }

    public function testExtractExprValueReturnsNullForConstFetchNull(): void
    {
        $result = $this->reader->callExtractExprValue(new ConstFetch(new Name('null')), [], '');

        self::assertNull($result);
    }

    public function testExtractExprValueReturnsNullForUnknownExprType(): void
    {
        $result = $this->reader->callExtractExprValue(new Variable('foo'), [], '');

        self::assertNull($result);
    }

    // -------------------------------------------------------------------------
    // extractExprValue — self::class and static::class resolve to $currentClass
    // -------------------------------------------------------------------------

    public function testExtractExprValueResolvesSelfClassToCurrentClass(): void
    {
        $expr   = new ClassConstFetch(new Name('self'), new Identifier('class'));
        $result = $this->reader->callExtractExprValue($expr, [], '', 'App\\MyClass');

        self::assertSame('App\\MyClass', $result);
    }

    public function testExtractExprValueResolvesStaticClassToCurrentClass(): void
    {
        $expr   = new ClassConstFetch(new Name('static'), new Identifier('class'));
        $result = $this->reader->callExtractExprValue($expr, [], '', 'App\\MyClass');

        self::assertSame('App\\MyClass', $result);
    }

    // -------------------------------------------------------------------------
    // extractExprValue — self::CASE and static::CASE resolve to currentClass::CASE
    // -------------------------------------------------------------------------

    public function testExtractExprValueResolvesSelfEnumCaseToCurrentClassCase(): void
    {
        $expr   = new ClassConstFetch(new Name('self'), new Identifier('MY_CASE'));
        $result = $this->reader->callExtractExprValue($expr, [], '', 'App\\MyEnum');

        self::assertSame('App\\MyEnum::MY_CASE', $result);
    }

    public function testExtractExprValueResolvesStaticEnumCaseToCurrentClassCase(): void
    {
        $expr   = new ClassConstFetch(new Name('static'), new Identifier('MY_CASE'));
        $result = $this->reader->callExtractExprValue($expr, [], '', 'App\\MyEnum');

        self::assertSame('App\\MyEnum::MY_CASE', $result);
    }

    // -------------------------------------------------------------------------
    // nameToFqn — FullyQualified name returns as-is
    // -------------------------------------------------------------------------

    public function testNameToFqnReturnsFullyQualifiedNameDirectly(): void
    {
        $result = $this->reader->callNameToFqn(new FullyQualified('Ns\\ClassName'), [], '');

        self::assertSame('Ns\\ClassName', $result);
    }

    // -------------------------------------------------------------------------
    // extractClassListFromArrayExpr — null item is skipped
    // -------------------------------------------------------------------------

    public function testExtractClassListFromArrayExprSkipsNullItems(): void
    {
        $item  = new ArrayItem(new ClassConstFetch(new FullyQualified('App\\Service'), new Identifier('class')));
        $array = new Array_([null, $item]);

        $result = $this->reader->callExtractClassListFromArrayExpr($array, [], 'App');

        self::assertSame(['App\\Service'], $result);
    }

    // -------------------------------------------------------------------------
    // buildEnumCaseExpr — input without '::' falls back to String_
    // -------------------------------------------------------------------------

    public function testBuildEnumCaseExprReturnsStringFallbackWhenNoDoubleColon(): void
    {
        $result = $this->reader->callBuildEnumCaseExpr('NoColonHere');

        self::assertInstanceOf(String_::class, $result);
        self::assertSame('NoColonHere', $result->value);
    }

    // -------------------------------------------------------------------------
    // newExprToFqn
    // -------------------------------------------------------------------------

    public function testNewExprToFqnReturnsNullForNonNewExpr(): void
    {
        $result = $this->reader->callNewExprToFqn(new Variable('x'), [], '');

        self::assertNull($result);
    }

    public function testNewExprToFqnReturnsNullForNullExpr(): void
    {
        $result = $this->reader->callNewExprToFqn(null, [], '');

        self::assertNull($result);
    }

    public function testNewExprToFqnReturnsFullyQualifiedNameDirectly(): void
    {
        $expr   = new New_(new FullyQualified('App\\Service'));
        $result = $this->reader->callNewExprToFqn($expr, [], '');

        self::assertSame('App\\Service', $result);
    }

    public function testNewExprToFqnResolvesShortNameViaUseMap(): void
    {
        $expr   = new New_(new Name('Service'));
        $result = $this->reader->callNewExprToFqn($expr, ['Service' => 'App\\Service'], 'App');

        self::assertSame('App\\Service', $result);
    }

    public function testNewExprToFqnPrependsNamespaceWhenNoAlias(): void
    {
        $expr   = new New_(new Name('Service'));
        $result = $this->reader->callNewExprToFqn($expr, [], 'App\\Provider');

        self::assertSame('App\\Provider\\Service', $result);
    }

    // -------------------------------------------------------------------------
    // extractClassListFromValues — new X() items
    // -------------------------------------------------------------------------

    public function testExtractClassListFromValuesHandlesNewExprItems(): void
    {
        $method = new ClassMethod(new Identifier('getProviders'));
        $item   = new ArrayItem(new New_(new FullyQualified('App\\Provider')));
        $array  = new Array_([$item]);
        $return = new Return_($array);

        $method->stmts = [$return];

        $result = $this->reader->callExtractClassListFromValues($method, [], 'App');

        self::assertSame(['App\\Provider'], $result);
    }

    // -------------------------------------------------------------------------
    // extractExprValue — new X() returns FQN string
    // -------------------------------------------------------------------------

    public function testExtractExprValueReturnsFqnForNewExpr(): void
    {
        $expr   = new New_(new FullyQualified('App\\Service'));
        $result = $this->reader->callExtractExprValue($expr, [], '');

        self::assertSame('App\\Service', $result);
    }

    public function testExtractExprValueReturnsFqnForNewExprWithUseMap(): void
    {
        $expr   = new New_(new Name('Service'));
        $result = $this->reader->callExtractExprValue($expr, ['Service' => 'App\\Service'], 'App');

        self::assertSame('App\\Service', $result);
    }

    // -------------------------------------------------------------------------
    // unwrapNamespace
    // -------------------------------------------------------------------------

    public function testUnwrapNamespaceReturnsNameAndInnerStatements(): void
    {
        $inner          = new ClassMethod(new Identifier('foo'));
        $ns             = new Namespace_(new Name('App\\Sub'), [$inner]);
        [$name, $stmts] = $this->reader->callUnwrapNamespace([$ns]);

        self::assertSame('App\\Sub', $name);
        self::assertSame([$inner], $stmts);
    }

    public function testUnwrapNamespaceReturnsEmptyNameWhenNoNamespace(): void
    {
        $stmt           = new ClassMethod(new Identifier('foo'));
        [$name, $stmts] = $this->reader->callUnwrapNamespace([$stmt]);

        self::assertSame('', $name);
        self::assertSame([$stmt], $stmts);
    }

    // -------------------------------------------------------------------------
    // buildUseMap
    // -------------------------------------------------------------------------

    public function testBuildUseMapResolvesAliasExplicitAndImplicit(): void
    {
        $use = new Use_([
            new UseItem(new Name('App\\Foo')),                      // implicit alias "Foo"
            new UseItem(new Name('App\\Bar'), new Identifier('B')), // explicit alias "B"
            new UseItem(new Name('Single')),                        // single-segment, no backslash
        ]);

        $map = $this->reader->callBuildUseMap([$use]);

        self::assertSame('App\\Foo', $map['Foo']);
        self::assertSame('App\\Bar', $map['B']);
        self::assertSame('Single', $map['Single']);
    }

    public function testBuildUseMapIgnoresNonUseStatements(): void
    {
        self::assertSame([], $this->reader->callBuildUseMap([new ClassMethod(new Identifier('x'))]));
    }

    // -------------------------------------------------------------------------
    // findClass / indexMethods
    // -------------------------------------------------------------------------

    public function testFindClassReturnsFirstClassNode(): void
    {
        $class = new Class_(new Identifier('Foo'));

        self::assertSame($class, $this->reader->callFindClass([new ClassMethod(new Identifier('x')), $class]));
    }

    public function testFindClassReturnsNullWhenNoClass(): void
    {
        self::assertNull($this->reader->callFindClass([new ClassMethod(new Identifier('x'))]));
    }

    public function testIndexMethodsIndexesByName(): void
    {
        $method = new ClassMethod(new Identifier('handle'));
        $class  = new Class_(new Identifier('Foo'), ['stmts' => [$method]]);

        $index = $this->reader->callIndexMethods($class);

        self::assertArrayHasKey('handle', $index);
        self::assertSame($method, $index['handle']);
    }

    // -------------------------------------------------------------------------
    // findAttributesOnNode / getAttrArg
    // -------------------------------------------------------------------------

    public function testFindAttributesOnNodeMatchesByFqn(): void
    {
        $attr   = new Attribute(new FullyQualified('App\\MyAttr'));
        $method = new ClassMethod(new Identifier('foo'), ['attrGroups' => [new AttributeGroup([$attr])]]);

        $matched = $this->reader->callFindAttributesOnNode($method, 'App\\MyAttr', [], '');
        $none    = $this->reader->callFindAttributesOnNode($method, 'App\\Other', [], '');

        self::assertSame([$attr], $matched);
        self::assertSame([], $none);
    }

    public function testGetAttrArgResolvesNamedPositionalAndAbsent(): void
    {
        $named      = new Arg(value: new String_('n'), name: new Identifier('foo'));
        $positional = new Arg(new String_('p'));

        self::assertInstanceOf(String_::class, $this->reader->callGetAttrArg([$named], 'foo', 0));
        self::assertInstanceOf(String_::class, $this->reader->callGetAttrArg([$positional], 'missing', 0));
        self::assertNull($this->reader->callGetAttrArg([], 'missing', 5));
    }

    // -------------------------------------------------------------------------
    // parseClassFile
    // -------------------------------------------------------------------------

    public function testParseClassFileReturnsContextForFileWithClass(): void
    {
        $path = $this->makeTempPhp("<?php\nnamespace App\\Demo;\nuse Other\\Thing;\nclass Foo {}\n");

        $context = $this->reader->callParseClassFile($path);

        self::assertNotNull($context);
        self::assertInstanceOf(Class_::class, $context[0]);
        self::assertSame('App\\Demo', $context[1]);
        self::assertSame('Other\\Thing', $context[2]['Thing']);
        self::assertSame('App\\Demo\\Foo', $context[3]);
    }

    public function testParseClassFileReturnsNullWhenNoClass(): void
    {
        $path = $this->makeTempPhp("<?php\n\$x = 1;\n");

        self::assertNull($this->reader->callParseClassFile($path));
    }

    // -------------------------------------------------------------------------
    // extractStringArg / extractBoolArg / extractClassListArg / extractStringListArg
    // -------------------------------------------------------------------------

    public function testExtractStringArgReturnsValueOrDefault(): void
    {
        $args = [new Arg(value: new String_('hello'), name: new Identifier('name'))];

        self::assertSame('hello', $this->reader->callExtractStringArg($args, 'name', 0, [], '', ''));
        self::assertSame('def', $this->reader->callExtractStringArg([], 'name', 0, [], '', '', 'def'));
    }

    public function testExtractBoolArgReturnsValueOrDefault(): void
    {
        $args = [new Arg(value: new ConstFetch(new Name('true')), name: new Identifier('flag'))];

        self::assertTrue($this->reader->callExtractBoolArg($args, 'flag', 0, [], '', ''));
        self::assertFalse($this->reader->callExtractBoolArg([], 'flag', 0, [], '', ''));
    }

    public function testExtractClassListArgReturnsListOrEmpty(): void
    {
        $array = new Array_([new ArrayItem(new ClassConstFetch(new FullyQualified('App\\X'), new Identifier('class')))]);
        $args  = [new Arg(value: $array, name: new Identifier('list'))];

        self::assertSame(['App\\X'], $this->reader->callExtractClassListArg($args, 'list', 0, [], '', ''));
        self::assertSame([], $this->reader->callExtractClassListArg([], 'list', 0, [], '', ''));
    }

    public function testExtractStringListFromArrayExprReturnsStrings(): void
    {
        $array = new Array_([new ArrayItem(new String_('a')), new ArrayItem(new String_('b'))]);

        self::assertSame(['a', 'b'], $this->reader->callExtractStringListFromArrayExpr($array, [], ''));
    }

    public function testExtractStringListArgReturnsListOrEmpty(): void
    {
        $array = new Array_([new ArrayItem(new String_('a'))]);
        $args  = [new Arg(value: $array, name: new Identifier('vals'))];

        self::assertSame(['a'], $this->reader->callExtractStringListArg($args, 'vals', 0, [], '', ''));
        self::assertSame([], $this->reader->callExtractStringListArg([], 'vals', 0, [], '', ''));
    }

    // -------------------------------------------------------------------------
    // build* helpers
    // -------------------------------------------------------------------------

    public function testBuildStringExpr(): void
    {
        $node = $this->reader->callBuildStringExpr('x');

        self::assertInstanceOf(String_::class, $node);
        self::assertSame('x', $node->value);
    }

    public function testBuildClassConstExpr(): void
    {
        $node = $this->reader->callBuildClassConstExpr('App\\X');

        self::assertInstanceOf(ClassConstFetch::class, $node);
        self::assertInstanceOf(FullyQualified::class, $node->class);
    }

    public function testBuildHandlerExpr(): void
    {
        $node = $this->reader->callBuildHandlerExpr(new HandlerData(class: 'App\\C', method: 'm'));

        self::assertInstanceOf(Array_::class, $node);
        self::assertCount(2, $node->items);
    }

    public function testBuildClassArrayExpr(): void
    {
        $node = $this->reader->callBuildClassArrayExpr(['App\\A', 'App\\B']);

        self::assertInstanceOf(Array_::class, $node);
        self::assertCount(2, $node->items);
    }

    public function testBuildNamedArg(): void
    {
        $arg = $this->reader->callBuildNamedArg('name', new String_('v'));

        self::assertInstanceOf(Arg::class, $arg);
        self::assertInstanceOf(Identifier::class, $arg->name);
        self::assertSame('name', $arg->name->toString());
    }

    public function testBuildNewExpr(): void
    {
        $node = $this->reader->callBuildNewExpr('App\\C', []);

        self::assertInstanceOf(New_::class, $node);
        self::assertInstanceOf(FullyQualified::class, $node->class);
    }

    public function testBuildBoolExpr(): void
    {
        $node = $this->reader->callBuildBoolExpr(true);

        self::assertInstanceOf(ConstFetch::class, $node);
        self::assertSame('true', $node->name->toString());
    }

    public function testBuildStringArrayExpr(): void
    {
        $node = $this->reader->callBuildStringArrayExpr(['a', 'b']);

        self::assertInstanceOf(Array_::class, $node);
        self::assertCount(2, $node->items);
    }

    public function testBuildEnumCaseArrayExpr(): void
    {
        $node = $this->reader->callBuildEnumCaseArrayExpr(['App\\E::CASE']);

        self::assertInstanceOf(Array_::class, $node);
        self::assertCount(1, $node->items);
    }

    // -------------------------------------------------------------------------
    // classConstFetchToFqn — self/static and short-name branches (via handler array)
    // -------------------------------------------------------------------------

    public function testExtractHandlerFromArrayResolvesSelfClassToCurrentClass(): void
    {
        $array = new Array_([
            new ArrayItem(new ClassConstFetch(new Name('self'), new Identifier('class'))),
            new ArrayItem(new String_('handle')),
        ]);

        $result = $this->reader->callExtractHandlerFromArray($array, [], '', 'App\\Current');

        self::assertInstanceOf(HandlerData::class, $result);
        self::assertSame('App\\Current', $result->class);
    }

    public function testExtractHandlerFromArrayResolvesShortClassNameViaNamespace(): void
    {
        $array = new Array_([
            new ArrayItem(new ClassConstFetch(new Name('Short'), new Identifier('class'))),
            new ArrayItem(new String_('handle')),
        ]);

        $result = $this->reader->callExtractHandlerFromArray($array, [], 'App', '');

        self::assertInstanceOf(HandlerData::class, $result);
        self::assertSame('App\\Short', $result->class);
    }

    // -------------------------------------------------------------------------
    // extractExprValue — regular enum case and array (handler) branches
    // -------------------------------------------------------------------------

    public function testExtractExprValueResolvesEnumCaseForRegularClass(): void
    {
        $expr = new ClassConstFetch(new FullyQualified('App\\E'), new Identifier('CASE'));

        self::assertSame('App\\E::CASE', $this->reader->callExtractExprValue($expr, [], ''));
    }

    public function testExtractExprValueResolvesArrayToHandlerData(): void
    {
        $array = new Array_([
            new ArrayItem(new ClassConstFetch(new FullyQualified('App\\C'), new Identifier('class'))),
            new ArrayItem(new String_('handle')),
        ]);

        $result = $this->reader->callExtractExprValue($array, [], '');

        self::assertInstanceOf(HandlerData::class, $result);
        self::assertSame('App\\C', $result->class);
    }

    // -------------------------------------------------------------------------
    // parseClassFile — no-namespace class + null array item skip
    // -------------------------------------------------------------------------

    public function testParseClassFileResolvesCurrentClassWithoutNamespace(): void
    {
        $path = $this->makeTempPhp("<?php\nclass Foo {}\n");

        $context = $this->reader->callParseClassFile($path);

        self::assertNotNull($context);
        self::assertSame('', $context[1]);
        self::assertSame('Foo', $context[3]);
    }

    public function testExtractStringListFromArrayExprSkipsNullItems(): void
    {
        $array = new Array_([null, new ArrayItem(new String_('a'))]);

        self::assertSame(['a'], $this->reader->callExtractStringListFromArrayExpr($array, [], ''));
    }

    /**
     * Write PHP source to a temp file and return its path (cleaned up on tearDown).
     */
    private function makeTempPhp(string $code): string
    {
        $path = tempnam(sys_get_temp_dir(), 'astreader') . '.php';
        file_put_contents($path, $code);
        $this->tempFiles[] = $path;

        return $path;
    }
}
