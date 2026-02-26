<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Metadata;

use WordPress\AiClient\Common\Exception\RuntimeException as AiClientRuntimeException;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\HttpTransporterFactory;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;
use Zaherg\OllamaAiProvider\Http\NoOpRequestAuthentication;
use Zaherg\OllamaAiProvider\Provider\OllamaProvider;

/**
 * Model metadata directory for Ollama via its OpenAI-compatible /v1/models endpoint.
 *
 * @phpstan-type ModelsResponseData array{
 *     data: list<array{id?: string}>
 * }
 */
class OllamaModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
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
            $data
        );
    }

    /**
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

    /**
     * Lazily initializes the HTTP transporter if the registry was created before the WordPress discovery strategy.
     *
     * {@inheritDoc}
     */
    public function getHttpTransporter(): HttpTransporterInterface
    {
        try {
            return parent::getHttpTransporter();
        } catch (AiClientRuntimeException $e) {
            $this->setHttpTransporter(HttpTransporterFactory::createTransporter());

            return parent::getHttpTransporter();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function parseResponseToModelMetadataList(Response $response): array
    {
        /** @var ModelsResponseData $responseData */
        $responseData = $response->getData();
        if (!isset($responseData['data']) || !is_array($responseData['data'])) {
            throw ResponseException::fromMissingData('Ollama', 'data');
        }

        $textCapabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        ];
        $textOptions = [
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::maxTokens()),
            new SupportedOption(OptionEnum::temperature()),
            new SupportedOption(OptionEnum::topP()),
            new SupportedOption(OptionEnum::stopSequences()),
            new SupportedOption(OptionEnum::functionDeclarations()),
            new SupportedOption(OptionEnum::outputMimeType(), ['text/plain', 'application/json']),
            new SupportedOption(OptionEnum::outputSchema()),
            new SupportedOption(OptionEnum::customOptions()),
            new SupportedOption(
                OptionEnum::inputModalities(),
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                ]
            ),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ];

        $models = [];
        foreach ($responseData['data'] as $index => $modelData) {
            if (!is_array($modelData) || !isset($modelData['id']) || !is_string($modelData['id'])) {
                throw ResponseException::fromInvalidData(
                    'Ollama',
                    "data[{$index}].id",
                    'The value must be a string.'
                );
            }

            $modelId = $modelData['id'];
            if ($this->isLikelyEmbeddingModel($modelId)) {
                continue;
            }

            $models[] = new ModelMetadata(
                $modelId,
                $modelId,
                $textCapabilities,
                $textOptions
            );
        }

        if ($models === []) {
            throw ResponseException::fromInvalidData(
                'Ollama',
                'data',
                'No text-capable models were returned by /v1/models.'
            );
        }

        usort($models, [$this, 'modelSortCallback']);

        return $models;
    }

    /**
     * Filters known embedding model naming patterns so only chat/text-capable models are advertised.
     *
     * @param string $modelId
     * @return bool
     */
    protected function isLikelyEmbeddingModel(string $modelId): bool
    {
        $lowerModelId = strtolower($modelId);

        return str_contains($lowerModelId, 'embed') || str_contains($lowerModelId, 'embedding');
    }

    /**
     * Sorts models for a more helpful UX (prefer latest tags, then alphabetically).
     *
     * @param ModelMetadata $a
     * @param ModelMetadata $b
     * @return int
     */
    protected function modelSortCallback(ModelMetadata $a, ModelMetadata $b): int
    {
        $aId = $a->getId();
        $bId = $b->getId();

        $aIsLatest = substr($aId, -7) === ':latest';
        $bIsLatest = substr($bId, -7) === ':latest';
        if ($aIsLatest && !$bIsLatest) {
            return -1;
        }
        if ($bIsLatest && !$aIsLatest) {
            return 1;
        }

        return strcmp($aId, $bId);
    }
}
