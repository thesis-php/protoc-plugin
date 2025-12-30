<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin;

use Thesis\Protobuf\Compiler\DescriptorProto;
use Thesis\Protobuf\Compiler\EnumDescriptorProto;
use Thesis\Protobuf\Compiler\EnumValueDescriptorProto;
use Thesis\Protobuf\Compiler\FieldDescriptorProto;
use Thesis\Protobuf\Compiler\FileDescriptorProto;
use Thesis\Protobuf\Compiler\Plugin\CodeGeneratorRequest;

/**
 * @api
 */
final readonly class Parser
{
    /**
     * @return array<string, array{FileDescriptor, list<ImportDescriptor>}>
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
                $descriptor = $protos[$file];
                $files[$file] = [$descriptor, self::parseImports($descriptor, $protos)];
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
            package: $descriptor->package,
            options: $descriptor->options,
            packageComments: $comments->extract(Comments::PACKAGE_COMMENT_PATH),
            syntaxComments: $comments->extract(Comments::SYNTAX_COMMENT_PATH),
            editionComments: $comments->extract(Comments::EDITION_COMMENT_PATH),
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
            $path = $descriptor->name;
            $messageComments = $comments->clone(\sprintf('%d.%d', Comments::MESSAGE_COMMENT_PATH, $idx));

            if ($parent !== null) {
                $path = "{$parent}.{$path}";
                $messageComments = $comments->clone(\sprintf('%d.%d', Comments::MESSAGE_MESSAGE_COMMENT_PATH, $idx));
            }

            $messages[] = new MessageDescriptor(
                name: $descriptor->name,
                path: $path,
                fields: self::parseMessageFields($file, $descriptor->fields, $messageComments),
                enums: self::parseEnums($descriptor->enumTypes, $messageComments, $descriptor->name),
                messages: self::parseMessages($file, $descriptor->nestedTypes, $messageComments, $descriptor->name),
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
            $fields[] = new FieldDescriptor(
                name: $descriptor->name,
                number: $descriptor->number,
                label: $descriptor->label,
                type: $descriptor->type,
                typeName: $descriptor->typeName,
                comment: $comments->extract(\sprintf('%d.%d', Comments::MESSAGE_FIELD_COMMENT_PATH, $idx)),
                options: $descriptor->options,
                optional: ($file->syntax === 'proto2' && $descriptor->label === FieldDescriptorProto\Label::LABEL_OPTIONAL) || ($file->syntax === 'proto3' && $descriptor->proto3Optional === true),
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
     * @param array<string, FileDescriptor> $files
     * @return list<ImportDescriptor>
     */
    private static function parseImports(
        FileDescriptor $descriptor,
        array $files,
    ): array {
        $imports = [];

        foreach ($descriptor->file->publicDependencies as $index) {
            $idx = (int) $index->value;

            if (!isset($descriptor->file->dependencies[$idx])) {
                continue;
            }

            $dependency = $descriptor->file->dependencies[$idx];

            if (!isset($files[$dependency])) {
                continue;
            }

            $file = $files[$dependency];

            foreach ($file->messages as $message) {
                if (isNotMapEntry($message)) {
                    $imports[] = new ImportDescriptor(
                        $message->name,
                        $message->path,
                    );
                }
            }

            foreach ($file->enums as $enum) {
                $imports[] = new ImportDescriptor(
                    $enum->name,
                    $enum->path,
                );
            }
        }

        return $imports;
    }
}
