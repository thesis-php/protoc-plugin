<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use BcMath\Number;
use Thesis\Package;
use Thesis\Protobuf\Compiler\Plugin;

/**
 * @api
 */
final readonly class Compiler
{
    private const string PACKAGE_NAME = 'thesis/protoc-plugin';

    private Parser $parser;

    /** @var non-empty-string */
    private string $version;

    public function __construct()
    {
        $this->parser = new Parser();
        $this->version = Package\version(self::PACKAGE_NAME);
    }

    public function compile(Plugin\CodeGeneratorRequest $request): Plugin\CodeGeneratorResponse
    {
        $options = CompilerOptions::fromRequest($request);

        $files = [];

        foreach ($this->parser->parse($request) as $source => [$proto, $imports]) {
            $phpNamespace = self::determinePhpNamespace($proto, $options);
            if ($phpNamespace === null) {
                return new Plugin\CodeGeneratorResponse(
                    error: 'Neither "package" nor "php_namespace" option was specified in the provided proto files, therefore I cannot determine the namespace under which the PHP files should be created.
If you cannot modify the proto files, please pass the namespace via command-line arguments as follows:
    --custom-plugin_out=php_namespace=App\\\Service\\\V1:path/to/generate',
                );
            }

            $generator = new Generator(
                namespace: $phpNamespace,
                path: str_replace('\\', '/', $phpNamespace),
                library: self::PACKAGE_NAME,
                pluginVersion: $this->version,
                protocVersion: (string) ($request->compilerVersion ?? 'unknown'),
                source: $source,
                package: $proto->package,
            );

            $files = [
                ...$files,
                ...array_map($generator->generateEnum(...), $proto->enums),
                ...array_map(
                    $generator->generateEnum(...),
                    array_merge(...array_map(static fn(MessageDescriptor $descriptor) => $descriptor->enums, $proto->messages)),
                ),
                ...array_map(
                    $generator->generateMessage(...),
                    array_filter($proto->messages, isNotMapEntry(...)),
                ),
                ...array_map(
                    $generator->generateMessage(...),
                    array_filter(
                        array_merge(...array_map(static fn(MessageDescriptor $descriptor) => $descriptor->messages, $proto->messages)),
                        isNotMapEntry(...),
                    ),
                ),
            ];
        }

        return new Plugin\CodeGeneratorResponse(
            supportFeatures: new Number(
                Plugin\CodeGeneratorResponse\Feature::FEATURE_PROTO3_OPTIONAL->value
                | Plugin\CodeGeneratorResponse\Feature::FEATURE_SUPPORTS_EDITIONS->value,
            ),
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
