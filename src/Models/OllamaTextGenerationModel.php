<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Models;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
use Zaherg\OllamaAiProvider\Http\OllamaHttpInitTrait;
use Zaherg\OllamaAiProvider\Provider\OllamaProvider;

/**
 * Text generation model for Ollama via its OpenAI-compatible /v1/chat/completions endpoint.
 */
class OllamaTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    use OllamaHttpInitTrait;

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
}
