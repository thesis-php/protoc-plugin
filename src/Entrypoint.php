<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use Google\Protobuf\Compiler\CodeGeneratorRequest;
use Google\Protobuf\Compiler\CodeGeneratorResponse;

/**
 * @api
 */
final readonly class Entrypoint
{
    public function __construct(
        private Plugin\Compiler $compiler,
        private ProtobufEncoder $protobuf,
    ) {}

    public function run(
        ReadInput $input,
        WriteOutput $output,
    ): void {
        try {
            $request = $this->protobuf->decode(
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

        $buffer = $this->protobuf->encode($response);

        if ($buffer !== '') {
            $output->write($buffer);
        }
    }
}
