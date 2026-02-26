<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Provider;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use Zaherg\OllamaAiProvider\Metadata\OllamaModelMetadataDirectory;
use Zaherg\OllamaAiProvider\Models\OllamaTextGenerationModel;

/**
 * Class for the AI Provider for Ollama.
 */
class OllamaProvider extends AbstractApiProvider
{
    /**
     * {@inheritDoc}
     */
    protected static function baseUrl(): string
    {
        if (defined('OLLAMA_BASE_URL')) {
            $configuredBaseUrl = constant('OLLAMA_BASE_URL');
            if (is_scalar($configuredBaseUrl) && (string) $configuredBaseUrl !== '') {
                return rtrim((string) $configuredBaseUrl, '/');
            }
        }

        return 'http://localhost:11434/v1';
    }

    /**
     * Returns the optional API key for Ollama, if configured.
     *
     * This is mainly useful when Ollama is exposed behind a proxy that requires a bearer token.
     * Local Ollama instances typically do not require authentication.
     *
     * @return string|null
     */
    public static function optionalApiKey(): ?string
    {
        $apiKey = static::readStringConfigValue('OLLAMA_API_KEY');
        if ($apiKey === null || $apiKey === '') {
            return null;
        }

        return $apiKey;
    }

    /**
     * {@inheritDoc}
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        $capabilities = $modelMetadata->getSupportedCapabilities();
        foreach ($capabilities as $capability) {
            if ($capability->isTextGeneration()) {
                return new OllamaTextGenerationModel($modelMetadata, $providerMetadata);
            }
        }

        throw new RuntimeException(
            'Unsupported model capabilities: ' . implode(', ', $capabilities)
        );
    }

    /**
     * {@inheritDoc}
     */
    protected static function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'ollama',
            'Ollama',
            ProviderTypeEnum::cloud(),
            'https://docs.ollama.com/cloud#cloud-api-access',
            RequestAuthenticationMethod::apiKey()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new class implements ProviderAvailabilityInterface
        {
            /**
             * {@inheritDoc}
             */
            public function isConfigured(): bool
            {
                // Local Ollama commonly runs without credentials. Let the real model-list
                // request determine runtime availability to avoid false "missing credentials" errors.
                return true;
            }
        };
    }

    /**
     * {@inheritDoc}
     */
    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new OllamaModelMetadataDirectory();
    }

    /**
     * Reads a provider configuration string from env var or constant.
     *
     * @param string $name Config key (e.g. OLLAMA_BASE_URL).
     * @return string|null
     */
    private static function readStringConfigValue(string $name): ?string
    {
        $value = getenv($name);
        if ($value !== false) {
            return is_string($value) ? $value : null;
        }

        if (defined($name)) {
            $constantValue = constant($name);
            if (is_scalar($constantValue)) {
                return (string) $constantValue;
            }
        }

        return null;
    }
}
