<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Registry\File;

/**
 * @api
 */
final class NameIndex
{
    /** @var list<File\MessageDescriptor> */
    public private(set) array $messageTypes = [];

    /** @var list<File\EnumDescriptor> */
    public private(set) array $enumTypes = [];

    /** @var list<File\ServiceDescriptor>
     */
    public private(set) array $services = [];

    public function addMessageType(File\MessageDescriptor $message): void
    {
        $this->messageTypes[] = $message;
    }

    public function addEnumType(File\EnumDescriptor $enum): void
    {
        $this->enumTypes[] = $enum;
    }

    public function addService(File\ServiceDescriptor $service): void
    {
        $this->services[] = $service;
    }

    public function empty(): bool
    {
        return $this->messageTypes === [] && $this->enumTypes === [] && $this->services === [];
    }
}
