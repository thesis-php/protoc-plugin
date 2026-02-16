<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use BcMath\Number;
use Google\Protobuf\Compiler\CodeGeneratorRequest;
use Google\Protobuf\Compiler\CodeGeneratorResponse;
use Thesis\Package;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;
use Thesis\Protoc\Plugin\Generator\FileFactory;
use Thesis\Protoc\Plugin\Parser\FileDescriptor;
use Thesis\Protoc\Plugin\Parser\MessageDescriptor;
use Thesis\Protoc\ProtobufEncoder;
use Thesis\Protoc\ProtocException;

/**
 * @api
 */
final readonly class Compiler
{
    private const string PLUGIN_NAME = 'thesis/protoc-plugin';
    public const int SUPPORTED_FEATURES = CodeGeneratorResponse\Feature::FEATURE_PROTO3_OPTIONAL->value
        | CodeGeneratorResponse\Feature::FEATURE_SUPPORTS_EDITIONS->value;

    private Parser $parser;

    public function __construct(
        private ProtobufEncoder $protobuf,
    ) {
        $this->parser = new Parser();
    }

    /**
     * @throws ProtocException
     * @throws \Throwable
     */
    public function compile(CodeGeneratorRequest $request): CodeGeneratorResponse
    {
        $options = CompilerOptions::fromRequest($request);

        $files = $this->doGenerate($request, $options);

        return new CodeGeneratorResponse(
            supportedFeatures: new Number(self::SUPPORTED_FEATURES),
            file: iterator_to_array($files, false),
        );
    }

    /**
     * @return iterable<CodeGeneratorResponse\File>
     * @throws ProtocException
     * @throws \Throwable
     */
    private function doGenerate(
        CodeGeneratorRequest $request,
        CompilerOptions $options,
    ): iterable {
        $request = $this->parser->parse($request);

        $registry = new Dependency\Registry($request, $options);

        $descriptorPaths = new PathTable();

        foreach ($request as $source => $proto) {
            $phpNamespace = self::determinePhpNamespace($proto, $options);

            $index = new NameIndex();

            $generator = new ClassLikeGenerator(
                namespace: $phpNamespace,
                files: new FileFactory(
                    self::createClassLikeGeneratedDoc($request, $source),
                    $path = $options->srcPath ?? str_replace('\\', '/', $phpNamespace),
                ),
                graph: $registry->graph($source),
                index: $index,
                package: $proto->package,
                syntax: $proto->syntax,
            );

            foreach ($proto->services as $service) {
                if ($options->requireGrpcClient) {
                    yield $generator->generateGrpcClient($service);
                }

                if ($options->requireGrpcServer) {
                    yield from $generator->generateGrpcServer($service);
                }
            }

            yield from array_map($generator->generateEnum(...), $proto->enums);

            foreach ($proto->messages as $descriptor) {
                yield from $this->doGenerateMessages($generator, $descriptor);
            }

            if (!$index->empty()) {
                $descriptorName = Naming::descriptorName($source);

                yield $generator->generateDescriptorMetadataRegistry(
                    $index,
                    $descriptorName,
                    $this->protobuf->encode($proto->file),
                );

                $descriptorPaths->addRelation("{$phpNamespace}\\{$descriptorName}", $path);
            }
        }

        foreach ($descriptorPaths->groupByNamespace() as $ns => $descriptors) {
            $generator = new AutoloadFunctionGenerator(
                new FileFactory(
                    self::createAutoloadFileGeneratedDoc($request),
                    $options->srcPath ?? str_replace('\\', '/', $ns),
                ),
            );

            yield $generator->generateAutoloadFile($descriptors);
        }
    }

    /**
     * @return iterable<CodeGeneratorResponse\File>
     */
    private function doGenerateMessages(ClassLikeGenerator $generator, MessageDescriptor $descriptor): iterable
    {
        if (!($descriptor->options?->mapEntry === true)) {
            yield from $generator->generateMessages($descriptor);
        }

        yield from array_map($generator->generateEnum(...), $descriptor->enums);

        foreach ($descriptor->messages as $message) {
            yield from $this->doGenerateMessages($generator, $message);
        }
    }

    /**
     * @return non-empty-string
     * @throws CodeCannotBeGenerated
     */
    private static function determinePhpNamespace(
        FileDescriptor $descriptor,
        CompilerOptions $options,
    ): string {
        if ($options->phpNamespace !== null) {
            return $options->phpNamespace;
        }

        $phpNamespace = $descriptor->options?->phpNamespace;
        if ($phpNamespace !== null && $phpNamespace !== '') {
            return $phpNamespace;
        }

        $package = $descriptor->package;
        if ($package !== null && $package !== '') {
            /** @var non-empty-string */
            return Naming::joinNamespace(explode('.', $package));
        }

        throw new CodeCannotBeGenerated('neither "package" nor "php_namespace" option was specified in the provided proto files, therefore I cannot determine the namespace under which the PHP files should be created.
If you cannot modify the proto files, please pass the namespace via command-line arguments as follows:
--custom-plugin_out=php_namespace=App\\\Service\\\V1:path/to/generated');
    }

    /**
     * @return non-empty-string
     */
    private static function createClassLikeGeneratedDoc(
        Parser\Request $request,
        string $source,
    ): string {
        return \sprintf(
            <<<'DOC'
Code generated by thesis/protoc-plugin. DO NOT EDIT.
Versions:
  thesis/protoc-plugin — v%s
  protoc               — v%s
Source: %s
DOC,
            Package\version(self::PLUGIN_NAME),
            self::createCompilerVersion($request),
            $source,
        );
    }

    /**
     * @return non-empty-string
     */
    private static function createAutoloadFileGeneratedDoc(
        Parser\Request $request,
    ): string {
        return \sprintf(
            <<<'DOC'
/**
 * Code generated by thesis/protoc-plugin. DO NOT EDIT.
 * Versions:
 *   thesis/protoc-plugin — v%s
 *   protoc               — v%s
 */
DOC,
            Package\version(self::PLUGIN_NAME),
            self::createCompilerVersion($request),
        );
    }

    private static function createCompilerVersion(Parser\Request $request): string
    {
        $compilerVersion = $request->request->compilerVersion;

        $version = implode('.', array_filter(
            [
                $compilerVersion?->major,
                $compilerVersion?->minor,
                $compilerVersion?->patch,
            ],
            static fn(?int $version) => $version !== null,
        ));

        if ($compilerVersion?->suffix !== null && $compilerVersion->suffix !== '') {
            $version .= '-' . $compilerVersion->suffix;
        }

        if ($version === '') {
            $version = 'unknown';
        }

        return $version;
    }
}
