<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Thrown when a BiletAll command cannot be sent or its response cannot be read.
 *
 * Rendered by Laravel as a 502 JSON response.
 */
class BiletAllException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $command,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function requestFailed(string $command, Throwable $previous): self
    {
        return new self(
            sprintf('BiletAll command [%s] failed: %s', $command, $previous->getMessage()),
            $command,
            $previous,
        );
    }

    public static function invalidResponse(string $command, Throwable $previous): self
    {
        return new self(
            sprintf('BiletAll command [%s] returned an unreadable response: %s', $command, $previous->getMessage()),
            $command,
            $previous,
        );
    }

    public function render(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => 'BiletAll servisine şu anda ulaşılamıyor',
            'error' => config('app.debug') ? $this->getMessage() : null,
        ], Response::HTTP_BAD_GATEWAY);
    }
}
