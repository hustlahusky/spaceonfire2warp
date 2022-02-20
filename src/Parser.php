<?php

declare(strict_types=1);

namespace App;

use Amp\Promise;
use App\Parser\PhpFile;
use App\Rules\RenameVisitor;
use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\Lexer;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Symfony\Contracts\Service\ResetInterface;
use function Amp\call;
use function Amp\File\filesystem;

final class Parser implements ResetInterface
{
    private readonly ErrorHandler\Collecting $errorHandler;

    private readonly NodeTraverserInterface $defaultTraverser;

    private readonly NodeTraverserInterface $transformTraverser;

    private function __construct(
        private readonly Version $version,
        private readonly PhpParser $parser,
        private readonly Lexer $lexer,
        private readonly PrettyPrinterAbstract $printer = new Standard(),
    ) {
        $this->errorHandler = new ErrorHandler\Collecting();
        $this->defaultTraverser = new NodeTraverser();
        $this->defaultTraverser->addVisitor(new NodeConnectingVisitor());
        $this->defaultTraverser->addVisitor(new NameResolver($this->errorHandler, [
            'replaceNodes' => false,
        ]));

        $replacement = match ($this->version) {
            Version::V2 => RenameVisitor::v2(),
            Version::V3 => RenameVisitor::v3(),
        };
        $reflection = new Reflection($this->parser, new ErrorHandler\Throwing(), $replacement);

        $this->transformTraverser = new NodeTraverser();
        $this->transformTraverser->addVisitor(new RenameVisitor($replacement, $reflection));

        if (Version::V3 === $this->version) {
            $this->transformTraverser->addVisitor(new Rules\Common\StaticConstructorVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\Common\ArrayHelperReplaceWithYiiVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\Criteria\CriteriaExprReplaceVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\Cycle\CycleRepositoryVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\DataSource\EntityNotFoundExceptionVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\DataSource\RepositoryInterfaceVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\Type\AggregateTypeVariadicVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\Type\BuiltinTypeConstructorArgumentsVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\ValueObject\ValueObjectNewSelfVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\ValueObject\ValueObjectCastVisitor($reflection));
            $this->transformTraverser->addVisitor(new Rules\ValueObject\ValueObjectValidateVisitor($reflection));
        }
    }

    public static function make(Version $version = Version::V3): self
    {
        $lexer = new Emulative([
            'phpVersion' => Emulative::PHP_8_1,
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startFilePos',
                'endFilePos',
                'startTokenPos',
                'endTokenPos',
            ],
        ]);
        $phpParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);

        return new self($version, $phpParser, $lexer);
    }

    public function reset(): void
    {
        $this->errorHandler->clearErrors();
    }

    /**
     * @return Promise<PhpFile>
     */
    public function loadFile(string $filename): Promise
    {
        return call(function () use ($filename) {
            $fs = filesystem();
            $code = yield $fs->read($filename);

            $ast = $this->parser->parse($code, $this->errorHandler) ?? [];

//            if ($errorHandler->hasErrors()) {
//                foreach ($errorHandler->getErrors() as $error) {
//                    \dump($error);
//                }
//
//                if ($stopOnError) {
//                    \die(1);
//                }
//            }

            $tokens = $this->lexer->getTokens();

            $ast = $this->defaultTraverser->traverse($ast);

            return new PhpFile($filename, $ast, $tokens);
        });
    }

    /**
     * @param Node[] $ast
     * @return Node[]
     */
    public function transform(array $ast): array
    {
        return $this->transformTraverser->traverse($ast);
    }

    /**
     * @param Node[] $ast
     * @return string
     */
    public function print(array $ast): string
    {
        return $this->printer->prettyPrintFile($ast);
    }

    /**
     * @param Node[] $ast
     * @param Node[] $originalAst
     * @param mixed[] $originalTokens
     * @return string
     */
    public function printFormatPreserving(array $ast, array $originalAst, array $originalTokens): string
    {
        return $this->printer->printFormatPreserving($ast, $originalAst, $originalTokens);
    }

    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errorHandler->getErrors();
    }
}
