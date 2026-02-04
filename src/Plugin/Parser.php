<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Compiler\DescriptorProto;
use Thesis\Protobuf\Compiler\EnumDescriptorProto;
use Thesis\Protobuf\Compiler\EnumValueDescriptorProto;
use Thesis\Protobuf\Compiler\FieldDescriptorProto;
use Thesis\Protobuf\Compiler\FileDescriptorProto;
use Thesis\Protobuf\Compiler\MethodDescriptorProto;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorRequest;
use Thesis\Protobuf\Compiler\ServiceDescriptorProto;

/**
 * @api
 */
final readonly class Parser
{
    /**
     * @return array<string, FileDescriptor>
     */
    public function parse(CodeGeneratorRequest $request): array
    {
        $protos = [];
        foreach ($request->protoFiles as $descriptor) {
            $protos[$descriptor->name] = self::parseFileDescriptor($descriptor);
        }

        $files = [];
        foreach ($request->filesToGenerate as $file) {
            if (isset($protos[$file])) {
                $files[$file] = $protos[$file];
            }
        }

        return $files;
    }

    private static function parseFileDescriptor(FileDescriptorProto $descriptor): FileDescriptor
    {
        $comments = new CommentExtractor(
            Comments::fromDescriptor($descriptor),
        );

        return new FileDescriptor(
            file: $descriptor,
            messages: self::parseMessages($descriptor, $descriptor->messages, $comments),
            enums: self::parseEnums($descriptor->enums, $comments),
            services: self::parseServices($descriptor->services, $comments),
            package: $descriptor->package,
            options: $descriptor->options,
            packageComments: $comments->extract(Comments::PACKAGE_COMMENT_PATH),
            syntaxComments: $comments->extract(Comments::SYNTAX_COMMENT_PATH),
            editionComments: $comments->extract(Comments::EDITION_COMMENT_PATH),
            syntax: $descriptor->syntax,
        );
    }

    /**
     * @param list<DescriptorProto> $descriptors
     * @return list<MessageDescriptor>
     */
    public static function parseMessages(
        FileDescriptorProto $file,
        array $descriptors,
        CommentExtractor $comments,
        ?string $parent = null,
    ): array {
        $messages = [];

        foreach ($descriptors as $idx => $descriptor) {
            if ($descriptor->name === null) {
                continue;
            }

            $path = $descriptor->name;
            $messageComments = $comments->clone(\sprintf('%d.%d', Comments::MESSAGE_COMMENT_PATH, $idx));

            if ($parent !== null) {
                $path = "{$parent}.{$path}";
                $messageComments = $comments->clone(\sprintf('%d.%d', Comments::MESSAGE_MESSAGE_COMMENT_PATH, $idx));
            }

            $oneofs = [];

            foreach ($descriptor->oneofs as $oneof) {
                if ($oneof->name !== null) {
                    $oneofs[] = new OneOfDescriptor(
                        $oneof->name,
                        $oneof->options,
                    );
                }
            }

            $messages[] = new MessageDescriptor(
                name: $descriptor->name,
                path: $path,
                fields: self::parseMessageFields($file, $descriptor->fields, $messageComments),
                enums: self::parseEnums($descriptor->enumTypes, $messageComments, $path),
                messages: self::parseMessages($file, $descriptor->nestedTypes, $messageComments, $path),
                oneofs: $oneofs,
                comment: $messageComments->extract(),
                options: $descriptor->options,
            );
        }

        return $messages;
    }

    /**
     * @param list<FieldDescriptorProto> $descriptors
     * @return list<FieldDescriptor>
     */
    private static function parseMessageFields(
        FileDescriptorProto $file,
        array $descriptors,
        CommentExtractor $comments,
    ): array {
        $fields = [];

        foreach ($descriptors as $idx => $descriptor) {
            if ($descriptor->name === null || $descriptor->number === null) {
                continue;
            }

            $fields[] = new FieldDescriptor(
                name: $descriptor->name,
                number: $descriptor->number,
                label: $descriptor->label,
                type: $descriptor->type,
                typeName: $descriptor->typeName,
                comment: $comments->extract(\sprintf('%d.%d', Comments::MESSAGE_FIELD_COMMENT_PATH, $idx)),
                options: $descriptor->options,
                optional: ($file->syntax === null && $descriptor->label === FieldDescriptorProto\Label::LABEL_OPTIONAL) || ($file->syntax === 'proto3' && $descriptor->proto3Optional === true),
                proto3Optional: $descriptor->proto3Optional,
                oneOfIndex: $descriptor->oneofIndex,
                defaultValue: $descriptor->defaultValue,
            );
        }

        return $fields;
    }

    /**
     * @param list<EnumDescriptorProto> $descriptors
     * @return list<EnumDescriptor>
     */
    private static function parseEnums(
        array $descriptors,
        CommentExtractor $comments,
        ?string $parent = null,
    ): array {
        $enums = [];

        foreach ($descriptors as $idx => $descriptor) {
            if ($descriptor->name === null) {
                continue;
            }

            $path = $descriptor->name;
            $enumComments = $comments->clone(\sprintf('%d.%d', Comments::ENUM_COMMENT_PATH, $idx));

            if ($parent !== null) {
                $path = "{$parent}.{$path}";
                $enumComments = $comments->clone(\sprintf('%d.%d', Comments::MESSAGE_ENUM_COMMENT_PATH, $idx));
            }

            $enums[] = new EnumDescriptor(
                $descriptor->name,
                $path,
                self::parseEnumCases(
                    $descriptor->values,
                    $enumComments,
                ),
                $enumComments->extract(),
                $descriptor->options,
            );
        }

        return $enums;
    }

    /**
     * @param list<EnumValueDescriptorProto> $descriptors
     * @return list<EnumCaseDescriptor>
     */
    private static function parseEnumCases(array $descriptors, CommentExtractor $comments): array
    {
        $cases = [];

        foreach ($descriptors as $idx => $descriptor) {
            if ($descriptor->name === null || $descriptor->number === null) {
                continue;
            }

            $cases[] = new EnumCaseDescriptor(
                $descriptor->name,
                $descriptor->number,
                $comments->extract(\sprintf('%s.%d', Comments::ENUM_VALUE_COMMENT_PATH, $idx)),
                $descriptor->options,
            );
        }

        return $cases;
    }

    /**
     * @param list<ServiceDescriptorProto> $descriptors
     * @return list<ServiceDescriptor>
     */
    private static function parseServices(array $descriptors, CommentExtractor $comments): array
    {
        $services = [];

        foreach ($descriptors as $idx => $descriptor) {
            if ($descriptor->name === null) {
                continue;
            }

            $commentPath = \sprintf('%d.%d', Comments::SERVICE_COMMENT_PATH, $idx);

            $services[] = new ServiceDescriptor(
                $name = $descriptor->name,
                $name,
                self::parseServiceMethods($descriptor->methods, $comments->clone($commentPath)),
                $comments->extract($commentPath),
            );
        }

        return $services;
    }

    /**
     * @param list<MethodDescriptorProto> $descriptors
     * @return list<ServiceMethodDescriptor>
     */
    private static function parseServiceMethods(
        array $descriptors,
        CommentExtractor $comments,
    ): array {
        $methods = [];

        foreach ($descriptors as $idx => $descriptor) {
            if ($descriptor->inputType === null || $descriptor->outputType === null || $descriptor->name === null) {
                continue;
            }

            $methods[] = new ServiceMethodDescriptor(
                name: $descriptor->name,
                inType: $descriptor->inputType,
                outType: $descriptor->outputType,
                clientStreaming: $descriptor->clientStreaming,
                serverStreaming: $descriptor->serverStreaming,
                comment: $comments->extract(\sprintf('%d.%d', Comments::SERVICE_METHOD_COMMENT_PATH, $idx)),
            );
        }

        return $methods;
    }
}
