<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use BcMath\Number;
use Google\Protobuf\Compiler\CodeGeneratorRequest;
use Google\Protobuf\Compiler\CodeGeneratorResponse;
use Google\Protobuf\Edition;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;

/**
 * @api
 */
final readonly class Entrypoint
{
    public const int SUPPORTED_FEATURES = CodeGeneratorResponse\Feature::FEATURE_PROTO3_OPTIONAL->value
        | CodeGeneratorResponse\Feature::FEATURE_SUPPORTS_EDITIONS->value;

    public function __construct(
        private Plugin\Compiler $compiler,
        private Encoder $encoder,
        private Decoder $decoder,
    ) {}

    public function run(
        ReadInput $input,
        WriteOutput $output,
    ): void {
        try {
            $request = $this->decoder->decode(
                $input->read(),
                CodeGeneratorRequest::class,
            );

            $response = self::createGeneratedResponse(files: $this->compiler->compile($request));
        } catch (ProtocException $e) {
            $response = self::createGeneratedResponse(error: $e->getMessage());
        } catch (\Throwable $e) {
            $response = self::createGeneratedResponse(error: "Generate code error: {$e->getMessage()}\n{$e->getTraceAsString()}");
        }

        $buffer = $this->encoder->encode($response);

        if ($buffer !== '') {
            $output->write($buffer);
        }
    }

    /**
     * @param list<CodeGeneratorResponse\File> $files
     */
    private static function createGeneratedResponse(array $files = [], ?string $error = null): CodeGeneratorResponse
    {
        return new CodeGeneratorResponse(
            error: $error,
            supportedFeatures: new Number(self::SUPPORTED_FEATURES),
            minimumEdition: Edition::EDITION_2023->value,
            maximumEdition: Edition::EDITION_2024->value,
            file: $files,
        );
    }
}
