<?php

declare(strict_types=1);

namespace WordPress\OllamaAiProvider\Models;

use WordPress\AiClient\Common\Exception\RuntimeException as AiClientRuntimeException;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
use WordPress\OllamaAiProvider\Http\NoOpRequestAuthentication;
use WordPress\OllamaAiProvider\Provider\OllamaProvider;

/**
 * Text generation model for Ollama via its OpenAI-compatible /v1/chat/completions endpoint.
 */
class OllamaTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    /**
     * {@inheritDoc}
     */
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        return new Request(
            $method,
            OllamaProvider::url($path),
            $headers,
            $data,
            $this->getRequestOptions()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareGenerateTextParams(array $prompt): array
    {
        $params = parent::prepareGenerateTextParams($prompt);
        $params['stream'] = false;

        return $params;
    }

    /**
     * Returns configured authentication or a local no-op fallback for typical Ollama setups.
     *
     * {@inheritDoc}
     */
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        try {
            return parent::getRequestAuthentication();
        } catch (AiClientRuntimeException $e) {
            $apiKey = OllamaProvider::optionalApiKey();
            if ($apiKey !== null) {
                return new ApiKeyRequestAuthentication($apiKey);
            }

            return new NoOpRequestAuthentication();
        }
    }
}
