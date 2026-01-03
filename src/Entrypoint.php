<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use Thesis\Protobuf;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorRequest;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorResponse;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Entrypoint
{
    public function __construct(
        private Plugin\Compiler $compiler,
        private Protobuf\Serializer $serializer,
        private Reflection\Reflector $reflector,
    ) {}

    public function run(
        ReadInput $input,
        WriteOutput $output,
    ): void {
        try {
            $request = $this->reflector->map(
                $this->serializer->deserialize(
                    $this->reflector->type(CodeGeneratorRequest::class),
                    $input->read(),
                ),
                CodeGeneratorRequest::class,
            );

            $response = $this->compiler->compile($request);
        } catch (ProtocException $e) {
            $response = new CodeGeneratorResponse(
                error: $e->getMessage(),
            );
        } catch (\Throwable $e) {
            $response = new CodeGeneratorResponse(
                error: "Generate code error: {$e->getTraceAsString()}.",
            );
        }

        $buffer = $this->serializer->serialize(
            $this->reflector->message($response),
        );

        if ($buffer !== '') {
            $output->write($buffer);
        }
    }
}
