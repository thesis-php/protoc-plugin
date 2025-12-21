<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use BcMath\Number;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorRequest;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorResponse;

/**
 * @api
 */
final readonly class Compiler
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function compile(CodeGeneratorRequest $request): CodeGeneratorResponse
    {
        $options = CompilerOptions::fromRequest($request);

        $files = [];

        foreach ($this->parser->parse($request) as $proto) {
            $phpNamespace = self::determinePhpNamespace($proto, $options);
            if ($phpNamespace === null) {
                return new CodeGeneratorResponse(
                    error: 'Neither "package" nor "php_namespace" option was specified in the provided proto files, therefore I cannot determine the namespace under which the PHP files should be created.
If you cannot modify the proto files, please pass the namespace via command-line arguments as follows:
    --custom-plugin_out=php_namespace=App\\\Service\\\V1:path/to/generate',
                );
            }

            $generator = new Generator(
                namespace: $phpNamespace,
                path: str_replace('\\', '/', $phpNamespace),
            );

            $files = [
                ...$files,
                ...array_map($generator->generateEnum(...), $proto->enums),
                ...array_map(
                    $generator->generateEnum(...),
                    array_merge(...array_map(static fn(MessageDescriptor $descriptor) => $descriptor->enums, $proto->messages)),
                ),
            ];
        }

        return new CodeGeneratorResponse(
            supportFeatures: new Number(CodeGeneratorResponse\Feature::FEATURE_PROTO3_OPTIONAL->value | CodeGeneratorResponse\Feature::FEATURE_SUPPORTS_EDITIONS->value),
            files: $files,
        );
    }

    /**
     * @return ?non-empty-string
     */
    private static function determinePhpNamespace(
        FileDescriptor $descriptor,
        CompilerOptions $options,
    ): ?string {
        $phpNamespace = $descriptor->options?->phpNamespace;
        if ($phpNamespace !== null && $phpNamespace !== '') {
            return $phpNamespace;
        }

        $package = $descriptor->package;
        if ($package !== null && $package !== '') {
            return implode('\\', array_map(ucwords(...), explode('.', $package)));
        }

        $phpNamespace = $options->phpNamespace();
        if ($phpNamespace !== null && $phpNamespace !== '') {
            return $phpNamespace;
        }

        return null;
    }
}
