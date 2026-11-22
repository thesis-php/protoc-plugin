<?php

declare(strict_types=1);

namespace Thesis\Protoc\GeneratedCode;

use BcMath\Number;
use Thesis\Google\Protobuf\FileDescriptorProto;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protobuf\Registry\Pool;
use Thesis\Protobuf\Registry\Registrar;

/**
 * Loads and exercises the PHP code of one snapshot case. It is meant to run in a dedicated child process
 * (see {@see GeneratedCodeTest}): snapshot cases declare classes with the same names, and a broken snapshot
 * may end the process with a fatal error.
 *
 * Every step is announced on STDOUT as a JSON line {"step": "..."} before it is taken, and every caught failure
 * is reported as {"fail": "...", "error": "..."}, so the parent can tell which file or class broke the process.
 */
final class SnapshotValidator
{
    private const string METADATA_FILE = 'autoload.metadata.php';
    private const string DESCRIPTOR_BUFFER_SUFFIX = '_DESCRIPTOR_BUFFER';

    /** @var list<class-string> messages and enums that must be registered in the descriptor pool */
    private array $types = [];

    /** @var list<class-string> */
    private array $messages = [];

    /** @var list<class-string<Registrar>> */
    private array $registries = [];

    /** @var array<class-string, true> classes registered in the pool by the snapshot registries */
    private array $registered = [];

    private int $failures = 0;

    /**
     * @param non-empty-string $root the snapshot case directory, the root of its namespaces
     * @param list<non-empty-string> $lintOnly files (relative to the root) that are not loaded
     */
    private function __construct(
        private readonly string $root,
        private readonly array $lintOnly,
        private readonly ?string $samples,
        private readonly Encoder $encoder,
        private readonly Decoder $decoder,
    ) {}

    /**
     * @param list<string> $argv [case directory, samples file or '', files of the case that are not loaded...]
     */
    public static function main(array $argv): int
    {
        ini_set('display_errors', 'stderr');
        error_reporting(E_ALL);
        set_error_handler(static function (int $level, string $message, string $file, int $line): never {
            throw new \ErrorException($message, 0, $level, $file, $line);
        });

        $root = realpath($argv[0] ?? '');
        if ($root === false || !is_dir($root)) {
            fwrite(STDERR, 'Snapshot case directory is not found: ' . ($argv[0] ?? '') . "\n");

            return 2;
        }

        $samples = $argv[1] ?? '';

        $validator = new self(
            $root,
            array_values(array_filter(\array_slice($argv, 2), static fn(string $file): bool => $file !== '')),
            $samples !== '' ? $samples : null,
            Encoder\Builder::buildDefault(),
            Decoder\Builder::buildDefault(),
        );

        return $validator->run();
    }

    private function run(): int
    {
        spl_autoload_register($this->autoload(...), prepend: true);

        $files = $this->files();

        foreach ($files as $file) {
            if (basename($file) !== self::METADATA_FILE && !\in_array($file, $this->lintOnly, true)) {
                $this->attempt("load {$file}", fn() => $this->load($file));
            }
        }

        foreach ($files as $file) {
            if (basename($file) === self::METADATA_FILE) {
                $this->attempt("execute {$file}", function () use ($file): void {
                    require $this->root . '/' . $file;
                });
            }
        }

        foreach ($this->registries as $registry) {
            $this->attempt("check registry {$registry}", fn() => $this->checkRegistry($registry));
        }

        foreach ($this->types as $type) {
            $this->attempt("check {$type} is registered", function () use ($type): void {
                if (!isset($this->registered[$type])) {
                    throw new \LogicException("{$type} is not registered in the descriptor pool by any autoload.metadata.php of the case.");
                }
            });
        }

        foreach ($this->messages as $message) {
            $this->attempt("instantiate and round-trip {$message}", function () use ($message): void {
                $constructor = new \ReflectionClass($message)->getConstructor();

                if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                    return; // only samples can build such a message
                }

                $this->roundTrip(new $message());
            });
        }

        if ($this->samples !== null) {
            $samples = $this->samples;
            $this->attempt("load samples {$samples}", function () use ($samples): void {
                $objects = require $samples;

                if (!\is_array($objects) || $objects === []) {
                    throw new \LogicException('The samples file must return a non-empty array of messages (or checks) keyed by label.');
                }

                foreach ($objects as $label => $object) {
                    if (!\is_string($label) || !\is_object($object)) {
                        throw new \LogicException("Sample {$label} must be a message (or a check) keyed by a string label.");
                    }

                    // A check inspects values of the generated code, e.g. the defaults of a message, and throws on a mismatch.
                    if ($object instanceof \Closure) {
                        $this->attempt("check sample '{$label}'", static function () use ($object): void {
                            $object();
                        });

                        continue;
                    }

                    $this->attempt("round-trip sample '{$label}'", fn() => $this->roundTrip($object));
                }
            });
        }

        return $this->failures === 0 ? 0 : 1;
    }

    /**
     * @return list<non-empty-string> PHP files relative to the root, sorted
     */
    private function files(): array
    {
        $files = [];

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = substr($file->getPathname(), \strlen($this->root) + 1);
            }
        }

        $files = array_values(array_filter($files, static fn(string $file): bool => $file !== ''));
        sort($files);

        return $files;
    }

    /**
     * Snapshot files are laid out PSR-4 style from the case root: Foo/Bar/Baz.php declares Foo\Bar\Baz.
     */
    private function autoload(string $class): void
    {
        $file = $this->root . '/' . str_replace('\\', '/', $class) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }

    /**
     * @param non-empty-string $file
     */
    private function load(string $file): void
    {
        $class = str_replace('/', '\\', substr($file, 0, -\strlen('.php')));

        if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
            throw new \LogicException("{$file} does not declare {$class}, though the snapshot layout maps the file to that name.");
        }

        $reflection = new \ReflectionClass($class);

        // Attributes (e.g. the runtime reflection of fields) are resolved only when instantiated.
        self::instantiateAttributes($reflection);

        if (enum_exists($class)) {
            self::checkEnum($class);
            $this->types[] = $class;

            return;
        }

        if ($reflection->isInterface()) {
            return;
        }

        if ($reflection->implementsInterface(Registrar::class)) {
            /** @var class-string<Registrar> $class */
            $this->registries[] = $class;

            return;
        }

        // Oneof variants implement the sealed oneof interface and are never registered on their own.
        if ($reflection->getInterfaceNames() === []) {
            $this->types[] = $class;
            $this->messages[] = $class;
        }
    }

    /**
     * @param class-string<\UnitEnum> $enum
     */
    private static function checkEnum(string $enum): void
    {
        $cases = $enum::cases();

        if (!is_subclass_of($enum, \BackedEnum::class)) {
            return;
        }

        // The values of a backed enum (duplicates among them) are validated only when the first value is accessed.
        foreach ($cases as $case) {
            \assert($case instanceof \BackedEnum);

            if ($enum::from($case->value) !== $case) {
                throw new \LogicException("{$enum}::from({$case->value}) is not {$enum}::{$case->name}.");
            }
        }
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private static function instantiateAttributes(\ReflectionClass $class): void
    {
        $reflections = [$class, ...$class->getReflectionConstants(), ...$class->getProperties(), ...$class->getMethods()];

        foreach ($class->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                // The attributes of a promoted parameter target its property, which is already in the list.
                if (!$parameter->isPromoted()) {
                    $reflections[] = $parameter;
                }
            }
        }

        foreach ($reflections as $reflection) {
            foreach ($reflection->getAttributes() as $attribute) {
                $attribute->newInstance();
            }
        }
    }

    /**
     * @param class-string<Registrar> $registry
     */
    private function checkRegistry(string $registry): void
    {
        $pool = Pool::get();
        $buffers = 0;

        foreach (new \ReflectionClass($registry)->getReflectionConstants() as $constant) {
            if (!str_ends_with($constant->getName(), self::DESCRIPTOR_BUFFER_SUFFIX)) {
                continue;
            }

            ++$buffers;
            $buffer = $constant->getValue();
            if (!\is_string($buffer)) {
                throw new \LogicException("{$registry}::{$constant->getName()} is not a string.");
            }

            $bytes = base64_decode($buffer, true);
            if ($bytes === false) {
                throw new \LogicException("{$registry}::{$constant->getName()} is not valid base64.");
            }

            $descriptor = $this->decoder->decode($bytes, FileDescriptorProto::class);
            $name = $descriptor->name ?? '';
            if ($name === '') {
                throw new \LogicException("{$registry}::{$constant->getName()} holds a FileDescriptorProto without a name.");
            }

            $file = $pool->fileByName($name);
            if ($file === null || $pool->descriptorByFilename($name)?->bytes !== $bytes) {
                throw new \LogicException("{$name} from {$registry} is not registered in the descriptor pool by any autoload.metadata.php of the case.");
            }

            foreach ($file->messages as $message) {
                if (!class_exists($message->fqcn)) {
                    throw new \LogicException("{$name}: message {$message->name} refers to the missing class {$message->fqcn}.");
                }

                $this->registered[$message->fqcn] = true;
            }

            foreach ($file->enums as $enum) {
                if (!enum_exists($enum->fqcn)) {
                    throw new \LogicException("{$name}: enum {$enum->name} refers to the missing enum {$enum->fqcn}.");
                }

                $this->registered[$enum->fqcn] = true;
            }
        }

        if ($buffers === 0) {
            throw new \LogicException("{$registry} has no descriptor buffer constants.");
        }
    }

    private function roundTrip(object $message): void
    {
        $bytes = $this->encoder->encode($message);
        $decoded = $this->decoder->decode($bytes, $message::class);

        $difference = self::difference($message, $decoded, $message::class);
        if ($difference !== null) {
            throw new \LogicException("Decoded message differs from the encoded one: {$difference}");
        }

        if ($this->encoder->encode($decoded) !== $bytes) {
            throw new \LogicException('Re-encoding the decoded message gives different bytes.');
        }
    }

    /**
     * Strict structural comparison: unlike ==, it tells null from '' and 0, and 1 from 1.0; only NaN equals NaN.
     */
    private static function difference(mixed $expected, mixed $actual, string $path): ?string
    {
        if (\is_object($expected) && \is_object($actual) && !$expected instanceof \UnitEnum) {
            if ($expected::class !== $actual::class) {
                return "{$path}: expected an instance of " . $expected::class . ', got ' . $actual::class;
            }

            if ($expected instanceof Number && $actual instanceof Number) {
                return $expected->compare($actual) === 0 ? null : "{$path}: expected {$expected}, got {$actual}";
            }

            foreach (new \ReflectionObject($expected)->getProperties() as $property) {
                if ($property->isStatic()) {
                    continue;
                }

                if (!$property->isInitialized($expected) || !$property->isInitialized($actual)) {
                    if ($property->isInitialized($expected) !== $property->isInitialized($actual)) {
                        return "{$path}->{$property->getName()}: initialized in only one of the messages";
                    }

                    continue;
                }

                $difference = self::difference($property->getValue($expected), $property->getValue($actual), "{$path}->{$property->getName()}");
                if ($difference !== null) {
                    return $difference;
                }
            }

            return null;
        }

        if (\is_array($expected) && \is_array($actual)) {
            if (array_keys($expected) !== array_keys($actual)) {
                return "{$path}: expected keys " . json_encode(array_keys($expected)) . ', got ' . json_encode(array_keys($actual));
            }

            foreach ($expected as $key => $value) {
                $difference = self::difference($value, $actual[$key] ?? null, "{$path}[{$key}]");
                if ($difference !== null) {
                    return $difference;
                }
            }

            return null;
        }

        // NAN !== NAN, yet a NaN that came back as a NaN is the same value.
        if (\is_float($expected) && \is_float($actual) && is_nan($expected) && is_nan($actual)) {
            return null;
        }

        return $expected === $actual ? null : "{$path}: expected " . get_debug_type($expected) . ' ' . var_export($expected, true) . ', got ' . get_debug_type($actual) . ' ' . var_export($actual, true);
    }

    /**
     * @param \Closure(): void $step
     */
    private function attempt(string $subject, \Closure $step): void
    {
        self::emit(['step' => $subject]);

        try {
            $step();
        } catch (\Throwable $e) {
            ++$this->failures;
            self::emit([
                'fail' => $subject,
                'error' => $e::class . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
            ]);
        }
    }

    /**
     * @param array<string, string> $event
     */
    private static function emit(array $event): void
    {
        fwrite(STDOUT, json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }
}
