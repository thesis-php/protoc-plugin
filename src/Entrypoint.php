<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use Thesis\Protobuf;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorRequest;
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

    public function run(ReadInput $input, WriteOutput $output): void
    {
        $request = $this->reflector->map(
            $this->serializer->deserialize(
                $this->reflector->type(CodeGeneratorRequest::class),
                $input->read(),
            ),
            CodeGeneratorRequest::class,
        );

        $response = $this->compiler->compile($request);

        $buffer = $this->serializer->serialize(
            $this->reflector->message($response),
        );

        if ($buffer !== '') {
            $output->write($buffer);
        }
    }
}
