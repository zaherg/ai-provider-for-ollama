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
        $configured = static::readStringConfigValue('OLLAMA_BASE_URL');
        if ($configured !== null && $configured !== '') {
            return rtrim($configured, '/');
        }

        return 'http://localhost:11434/v1';
    }

    /**
     * Returns the configured API key for authenticated server deployments, if present.
     *
     * This is used when the configured endpoint requires bearer token authentication.
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
            null,
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
                // Let the real model-list request determine runtime availability to avoid
                // false "missing credentials" or connectivity errors during registration.
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
     * Reads a provider configuration string from a PHP constant (e.g. wp-config.php).
     *
     * @param string $name Config key (e.g. OLLAMA_BASE_URL).
     * @return string|null
     */
    protected static function readStringConfigValue(string $name): ?string
    {
        if (defined($name)) {
            $value = constant($name);
            if (is_scalar($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
