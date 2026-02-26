<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Http;

use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;

/**
 * No-op request authentication for local Ollama instances that do not require credentials.
 */
final class NoOpRequestAuthentication implements RequestAuthenticationInterface
{
    /**
     * {@inheritDoc}
     */
    public function authenticateRequest(Request $request): Request
    {
        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
            'additionalProperties' => false,
        ];
    }
}
