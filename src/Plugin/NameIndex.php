<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

/**
 * @api
 * @phpstan-type GrpcService = object{client: ?string, server: ?string}
 */
final class NameIndex
{
    /** @var array<string, string> */
    public private(set) array $messageTypes = [];

    /** @var array<string, string> */
    public private(set) array $enumTypes = [];

    /** @var array<string, GrpcService>
     */
    public private(set) array $grpc = [];

    public function addMessageType(string $type, string $fqcn): void
    {
        $this->messageTypes[$type] = $fqcn;
    }

    public function addEnumType(string $type, string $fqcn): void
    {
        $this->enumTypes[$type] = $fqcn;
    }

    public function addClient(string $type, string $fqcn): void
    {
        $this->grpc[$type] ??= new class {
            public function __construct(
                public ?string $client = null,
                public ?string $server = null,
            ) {}
        };
        $this->grpc[$type]->client = $fqcn; // @phpstan-ignore assign.propertyReadOnly
    }

    public function addServer(string $type, string $fqcn): void
    {
        $this->grpc[$type] ??= new class {
            public function __construct(
                public ?string $client = null,
                public ?string $server = null,
            ) {}
        };
        $this->grpc[$type]->server = $fqcn; // @phpstan-ignore assign.propertyReadOnly
    }

    public function empty(): bool
    {
        return $this->messageTypes === [] && $this->enumTypes === [] && $this->grpc === [];
    }
}
