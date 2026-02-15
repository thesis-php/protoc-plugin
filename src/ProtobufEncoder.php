<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;

/**
 * @api
 */
final readonly class ProtobufEncoder
{
    public function __construct(
        private Serializer $serializer,
        private Reflector $reflector,
    ) {}

    public static function default(): self
    {
        return new self(
            new Serializer(),
            Reflector::build(),
        );
    }

    public function encode(object $message): string
    {
        return $this->serializer->serialize(
            $this->reflector->message($message),
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $classType
     * @return T
     */
    public function decode(string $buffer, string $classType): object
    {
        return $this->reflector->map(
            $this->serializer->deserialize(
                $this->reflector->type($classType),
                $buffer,
            ),
            $classType,
        );
    }
}
