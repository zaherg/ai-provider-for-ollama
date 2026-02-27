<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Http;

use WordPress\AiClient\Common\Exception\RuntimeException as AiClientRuntimeException;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\HttpTransporterFactory;
use Zaherg\OllamaAiProvider\Provider\OllamaProvider;

/**
 * Shared HTTP initialization for Ollama classes that extend different SDK base classes.
 *
 * Provides fallback authentication and lazy HTTP transporter initialization
 * needed when the provider is registered before WordPress has fully bootstrapped.
 */
trait OllamaHttpInitTrait
{
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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('Ollama: authentication fallback triggered — ' . $e->getMessage());
            }

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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('Ollama: HTTP transporter fallback triggered — ' . $e->getMessage());
            }

            $this->setHttpTransporter(HttpTransporterFactory::createTransporter());

            return parent::getHttpTransporter();
        }
    }
}
