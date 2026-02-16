<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use Google\Protobuf\Compiler\CodeGeneratorRequest;
use Google\Protobuf\Compiler\CodeGeneratorResponse;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;

/**
 * @api
 */
final readonly class Entrypoint
{
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

            $response = $this->compiler->compile($request);
        } catch (ProtocException $e) {
            $response = new CodeGeneratorResponse(
                error: $e->getMessage(),
            );
        } catch (\Throwable $e) {
            $response = new CodeGeneratorResponse(
                error: "Generate code error: {$e->getMessage()}\n{$e->getTraceAsString()}",
            );
        }

        $buffer = $this->encoder->encode($response);

        if ($buffer !== '') {
            $output->write($buffer);
        }
    }
}
