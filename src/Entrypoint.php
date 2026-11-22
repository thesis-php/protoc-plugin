<?php

declare(strict_types=1);

namespace Thesis\Protoc;

use BcMath\Number;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorRequest;
use Thesis\Google\Protobuf\Compiler\CodeGeneratorResponse;
use Thesis\Google\Protobuf\Edition;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;

/**
 * @api
 */
final readonly class Entrypoint
{
    public const int SUPPORTED_FEATURES = CodeGeneratorResponse\Feature::FEATURE_PROTO3_OPTIONAL->value
        | CodeGeneratorResponse\Feature::FEATURE_SUPPORTS_EDITIONS->value;

    public function __construct(
        private Plugin\Compiler $compiler,
        private Encoder $encoder,
        private Decoder $decoder,
    ) {}

    /**
     * While running, PHP diagnostics never reach STDOUT (which carries the binary response):
     * warnings and notices are converted to {@see \ErrorException} and reported via {@see CodeGeneratorResponse::$error},
     * deprecations and errors silenced by `@` or `error_reporting` are left to PHP's default handling,
     * with `display_errors` redirected from STDOUT to STDERR. The previous error handler and `display_errors` are restored afterwards.
     */
    public function run(
        ReadInput $input,
        WriteOutput $output,
    ): void {
        $displayErrors = self::redirectDisplayErrorsToStderr();
        set_error_handler(self::handleError(...));

        try {
            $this->generate($input, $output);
        } finally {
            restore_error_handler();

            if ($displayErrors !== null) {
                ini_set('display_errors', $displayErrors);
            }
        }
    }

    private function generate(ReadInput $input, WriteOutput $output): void
    {
        try {
            $request = $this->decoder->decode(
                $input->read(),
                CodeGeneratorRequest::class,
            );

            $response = self::createGeneratedResponse(files: $this->compiler->compile($request));
        } catch (ProtocException $e) {
            $response = self::createGeneratedResponse(error: $e->getMessage());
        } catch (\Throwable $e) {
            $response = self::createGeneratedResponse(error: "Generate code error: {$e->getMessage()}\n{$e->getTraceAsString()}");
        }

        $buffer = $this->encoder->encode($response);

        if ($buffer !== '') {
            $output->write($buffer);
        }
    }

    /**
     * @throws \ErrorException
     */
    private static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if ((error_reporting() & $errno) === 0) {
            return false;
        }

        if (($errno & (\E_DEPRECATED | \E_USER_DEPRECATED)) !== 0) {
            return false;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * @return ?string the previous value of `display_errors` if it was changed
     */
    private static function redirectDisplayErrorsToStderr(): ?string
    {
        $value = \ini_get('display_errors');

        if ($value === false) {
            return null;
        }

        // Mirrors how PHP interprets display_errors: on/yes/true/stdout and any non-zero number except 2 mean STDOUT.
        $toStdout = match (strtolower(trim($value))) {
            'on', 'yes', 'true', 'stdout' => true,
            'stderr' => false,
            default => !\in_array((int) $value, [0, 2], true),
        };

        if (!$toStdout) {
            return null;
        }

        $previous = ini_set('display_errors', 'stderr');

        return $previous === false ? null : $previous;
    }

    /**
     * @param list<CodeGeneratorResponse\File> $files
     */
    private static function createGeneratedResponse(array $files = [], ?string $error = null): CodeGeneratorResponse
    {
        return new CodeGeneratorResponse(
            error: $error,
            supportedFeatures: new Number(self::SUPPORTED_FEATURES),
            minimumEdition: Edition::EDITION_2023->value,
            maximumEdition: Edition::EDITION_2024->value,
            file: $files,
        );
    }
}
