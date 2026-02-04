<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use BcMath\Number;
use Thesis\Package;
use Thesis\Protobuf\Compiler\Plugin;
use Thesis\Protoc\Exception\CodeCannotBeGenerated;
use Thesis\Protoc\ProtocException;

/**
 * @api
 */
final readonly class Compiler
{
    private const string PLUGIN_NAME = 'thesis/protoc-plugin';
    public const int SUPPORTED_FEATURES = Plugin\CodeGeneratorResponse\Feature::FEATURE_PROTO3_OPTIONAL->value
        | Plugin\CodeGeneratorResponse\Feature::FEATURE_SUPPORTS_EDITIONS->value;

    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * @throws ProtocException
     * @throws \Throwable
     */
    public function compile(Plugin\CodeGeneratorRequest $request): Plugin\CodeGeneratorResponse
    {
        $options = CompilerOptions::fromRequest($request);

        $files = $this->doGenerate($request, $options);

        return new Plugin\CodeGeneratorResponse(
            supportFeatures: new Number(self::SUPPORTED_FEATURES),
            files: iterator_to_array($files, false),
        );
    }

    /**
     * @return iterable<Plugin\CodeGeneratorResponse\File>
     * @throws ProtocException
     * @throws \Throwable
     */
    private function doGenerate(
        Plugin\CodeGeneratorRequest $request,
        CompilerOptions $options,
    ): iterable {
        $protos = $this->parser->parse($request);

        foreach ($protos as $source => $proto) {
            $phpNamespace = self::determinePhpNamespace($proto, $options);

            $generator = new Generator(
                namespace: $phpNamespace,
                path: str_replace('\\', '/', $phpNamespace),
                pluginVersion: Package\version(self::PLUGIN_NAME),
                protocVersion: (string) ($request->compilerVersion ?? 'unknown'),
                source: $source,
                package: $proto->package,
                syntax: $proto->syntax,
                protos: $protos,
            );

            $requireGrpcClient = $options->requireGrpcClient();
            $requireGrpcServer = $options->requireGrpcServer();

            foreach ($proto->services as $service) {
                if ($requireGrpcClient) {
                    yield from $generator->generateGrpcClient($service);
                }

                if ($requireGrpcServer) {
                    yield from $generator->generateGrpcServer($service);
                }
            }

            yield from array_map($generator->generateEnum(...), $proto->enums);

            foreach ($proto->messages as $descriptor) {
                yield from $this->doGenerateMessages($generator, $descriptor);
            }
        }
    }

    /**
     * @return iterable<Plugin\CodeGeneratorResponse\File>
     */
    private function doGenerateMessages(Generator $generator, MessageDescriptor $descriptor): iterable
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
        $phpNamespace = $descriptor->options?->phpNamespace;
        if ($phpNamespace !== null && $phpNamespace !== '') {
            return $phpNamespace;
        }

        $package = $descriptor->package;
        if ($package !== null && $package !== '') {
            /** @var non-empty-string */
            return Naming::joinNamespace(explode('.', $package));
        }

        $phpNamespace = $options->phpNamespace();
        if ($phpNamespace !== null && $phpNamespace !== '') {
            return $phpNamespace;
        }

        throw new CodeCannotBeGenerated('neither "package" nor "php_namespace" option was specified in the provided proto files, therefore I cannot determine the namespace under which the PHP files should be created.
If you cannot modify the proto files, please pass the namespace via command-line arguments as follows:
    --custom-plugin_out=php_namespace=App\\\Service\\\V1:path/to/generated');
    }
}
