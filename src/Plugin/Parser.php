<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Compiler;

/**
 * @api
 */
final readonly class Parser
{
    public function parse(Compiler\Plugin\CodeGeneratorRequest $request): Parser\Request
    {
        $protos = [];
        foreach ($request->protoFiles as $descriptor) {
            if ($descriptor->name === null || $descriptor->name === '') {
                continue;
            }

            $protos[$descriptor->name] = self::parseFileDescriptor($descriptor, $descriptor->name);
        }

        return new Parser\Request($request, $protos);
    }

    /**
     * @param non-empty-string $name
     */
    private static function parseFileDescriptor(Compiler\FileDescriptorProto $descriptor, string $name): Parser\FileDescriptor
    {
        $comments = new CommentExtractor(
            Comments::fromDescriptor($descriptor),
        );

        return new Parser\FileDescriptor(
            name: $name,
            file: $descriptor,
            messages: self::parseMessages($descriptor, $descriptor->messages, $comments),
            enums: self::parseEnums($descriptor->enums, $comments),
            services: self::parseServices($descriptor->services, $comments),
            dependencies: $descriptor->dependencies,
            package: $descriptor->package,
            options: $descriptor->options,
            packageComments: $comments->extract(Comments::PACKAGE_COMMENT_PATH),
            syntaxComments: $comments->extract(Comments::SYNTAX_COMMENT_PATH),
            editionComments: $comments->extract(Comments::EDITION_COMMENT_PATH),
            syntax: $descriptor->syntax,
        );
    }

    /**
     * @param list<Compiler\DescriptorProto> $descriptors
     * @return list<Parser\MessageDescriptor>
     */
    public static function parseMessages(
        Compiler\FileDescriptorProto $file,
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
                    $oneofs[] = new Parser\OneOfDescriptor(
                        $oneof->name,
                        $oneof->options,
                    );
                }
            }

            $messages[] = new Parser\MessageDescriptor(
                name: $descriptor->name,
                path: $path,
                fields: self::parseMessageFields($descriptor, $file, $descriptor->fields, $messageComments),
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
     * @param list<Compiler\FieldDescriptorProto> $descriptors
     * @return list<Parser\FieldDescriptor>
     */
    private static function parseMessageFields(
        Compiler\DescriptorProto $message,
        Compiler\FileDescriptorProto $file,
        array $descriptors,
        CommentExtractor $comments,
    ): array {
        $fields = [];

        foreach ($descriptors as $idx => $descriptor) {
            if ($descriptor->name === null || $descriptor->number === null) {
                continue;
            }

            $map = null;

            $maybeMap = $descriptor->label === Compiler\FieldDescriptorProto\Label::LABEL_REPEATED
                && $descriptor->type === Compiler\FieldDescriptorProto\Type::TYPE_MESSAGE;

            if ($maybeMap) {
                foreach ($message->nestedTypes as $nestedType) {
                    $typename = ($file->package !== null ? ".{$file->package}." : '.') . "{$message->name}.{$nestedType->name}";

                    if ($typename === $descriptor->typeName && $nestedType->options?->mapEntry === true) {
                        \assert(\count($nestedType->fields) === 2, 'Each MapEntry must have exactly 2 fields.');

                        $map = new Parser\MapDescriptor(
                            self::createFieldDescriptor($nestedType->fields[0], $file),
                            self::createFieldDescriptor($nestedType->fields[1], $file),
                        );

                        break;
                    }
                }
            }

            $fields[] = self::createFieldDescriptor(
                $descriptor,
                $file,
                $comments->extract(\sprintf('%d.%d', Comments::MESSAGE_FIELD_COMMENT_PATH, $idx)),
                $map,
            );
        }

        return $fields;
    }

    /**
     * @param list<Compiler\EnumDescriptorProto> $descriptors
     * @return list<Parser\EnumDescriptor>
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

            $enums[] = new Parser\EnumDescriptor(
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
     * @param list<Compiler\EnumValueDescriptorProto> $descriptors
     * @return list<Parser\EnumCaseDescriptor>
     */
    private static function parseEnumCases(array $descriptors, CommentExtractor $comments): array
    {
        $cases = [];

        foreach ($descriptors as $idx => $descriptor) {
            if ($descriptor->name === null || $descriptor->number === null) {
                continue;
            }

            $cases[] = new Parser\EnumCaseDescriptor(
                $descriptor->name,
                $descriptor->number,
                $comments->extract(\sprintf('%s.%d', Comments::ENUM_VALUE_COMMENT_PATH, $idx)),
                $descriptor->options,
            );
        }

        return $cases;
    }

    /**
     * @param list<Compiler\ServiceDescriptorProto> $descriptors
     * @return list<Parser\ServiceDescriptor>
     */
    private static function parseServices(array $descriptors, CommentExtractor $comments): array
    {
        $services = [];

        foreach ($descriptors as $idx => $descriptor) {
            if ($descriptor->name === null) {
                continue;
            }

            $commentPath = \sprintf('%d.%d', Comments::SERVICE_COMMENT_PATH, $idx);

            $services[] = new Parser\ServiceDescriptor(
                $name = $descriptor->name,
                $name,
                self::parseServiceMethods($descriptor->methods, $comments->clone($commentPath)),
                $comments->extract($commentPath),
            );
        }

        return $services;
    }

    /**
     * @param list<Compiler\MethodDescriptorProto> $descriptors
     * @return list<Parser\ServiceMethodDescriptor>
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

            $methods[] = new Parser\ServiceMethodDescriptor(
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

    private static function createFieldDescriptor(
        Compiler\FieldDescriptorProto $descriptor,
        Compiler\FileDescriptorProto $file,
        ?Comment $comment = null,
        ?Parser\MapDescriptor $map = null,
    ): Parser\FieldDescriptor {
        \assert($descriptor->name !== null, 'Field name must not be null.');
        \assert($descriptor->number !== null, 'Field number must not be null.');

        return new Parser\FieldDescriptor(
            name: $descriptor->name,
            number: $descriptor->number,
            label: $descriptor->label,
            type: $descriptor->type,
            typeName: $descriptor->typeName,
            comment: $comment,
            options: $descriptor->options,
            optional: ($file->syntax === null && $descriptor->label === Compiler\FieldDescriptorProto\Label::LABEL_OPTIONAL) || ($file->syntax === 'proto3' && $descriptor->proto3Optional === true),
            proto3Optional: $descriptor->proto3Optional,
            oneOfIndex: $descriptor->oneofIndex,
            defaultValue: $descriptor->defaultValue,
            map: $map,
        );
    }
}
