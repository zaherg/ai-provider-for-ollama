<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Tests\Unit\Provider;

use ReflectionMethod;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Zaherg\OllamaAiProvider\Metadata\OllamaModelMetadataDirectory;
use Zaherg\OllamaAiProvider\Models\OllamaTextGenerationModel;
use Zaherg\OllamaAiProvider\Provider\OllamaProvider;

class OllamaProviderTest extends TestCase
{
    protected function tear_down(): void
    {
        // Clean up any constants we may have defined — not possible in PHP,
        // so we rely on running constant-dependent tests in separate processes.
        parent::tear_down();
    }

    public function testBaseUrlReturnsDefaultWhenNoConstantDefined(): void
    {
        $method = new ReflectionMethod(OllamaProvider::class, 'baseUrl');
        $method->setAccessible(true);

        $this->assertSame('http://localhost:11434/v1', $method->invoke(null));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBaseUrlReturnsCustomUrlWhenConstantDefined(): void
    {
        define('OLLAMA_BASE_URL', 'http://my-ollama:8080/v1/');

        $method = new ReflectionMethod(OllamaProvider::class, 'baseUrl');
        $method->setAccessible(true);

        $this->assertSame('http://my-ollama:8080/v1', $method->invoke(null));
    }

    public function testOptionalApiKeyReturnsNullWhenNoConstant(): void
    {
        $this->assertNull(OllamaProvider::optionalApiKey());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testOptionalApiKeyReturnsKeyWhenConstantDefined(): void
    {
        define('OLLAMA_API_KEY', 'sk-test-key-123');

        $this->assertSame('sk-test-key-123', OllamaProvider::optionalApiKey());
    }

    public function testCreateProviderMetadataReturnsCorrectMetadata(): void
    {
        $method = new ReflectionMethod(OllamaProvider::class, 'createProviderMetadata');
        $method->setAccessible(true);

        /** @var ProviderMetadata $metadata */
        $metadata = $method->invoke(null);

        $this->assertInstanceOf(ProviderMetadata::class, $metadata);
        $this->assertSame('ollama', $metadata->getId());
        $this->assertSame('Ollama', $metadata->getName());
    }

    public function testCreateProviderAvailabilityReturnsConfiguredTrue(): void
    {
        $method = new ReflectionMethod(OllamaProvider::class, 'createProviderAvailability');
        $method->setAccessible(true);

        /** @var ProviderAvailabilityInterface $availability */
        $availability = $method->invoke(null);

        $this->assertInstanceOf(ProviderAvailabilityInterface::class, $availability);
        $this->assertTrue($availability->isConfigured());
    }

    public function testCreateModelMetadataDirectoryReturnsOllamaInstance(): void
    {
        $method = new ReflectionMethod(OllamaProvider::class, 'createModelMetadataDirectory');
        $method->setAccessible(true);

        $directory = $method->invoke(null);

        $this->assertInstanceOf(OllamaModelMetadataDirectory::class, $directory);
        $this->assertInstanceOf(ModelMetadataDirectoryInterface::class, $directory);
    }

    public function testCreateModelWithTextGenerationCapabilityReturnsOllamaTextGenerationModel(): void
    {
        $method = new ReflectionMethod(OllamaProvider::class, 'createModel');
        $method->setAccessible(true);

        $modelMetadata = new ModelMetadata(
            'llama3:latest',
            'llama3:latest',
            [CapabilityEnum::textGeneration()],
            []
        );

        $providerMethod = new ReflectionMethod(OllamaProvider::class, 'createProviderMetadata');
        $providerMethod->setAccessible(true);
        /** @var ProviderMetadata $providerMetadata */
        $providerMetadata = $providerMethod->invoke(null);

        $model = $method->invoke(null, $modelMetadata, $providerMetadata);

        $this->assertInstanceOf(OllamaTextGenerationModel::class, $model);
    }

    public function testCreateModelWithUnsupportedCapabilityThrowsRuntimeException(): void
    {
        $method = new ReflectionMethod(OllamaProvider::class, 'createModel');
        $method->setAccessible(true);

        $modelMetadata = new ModelMetadata(
            'some-model',
            'some-model',
            [CapabilityEnum::chatHistory()],
            []
        );

        $providerMethod = new ReflectionMethod(OllamaProvider::class, 'createProviderMetadata');
        $providerMethod->setAccessible(true);
        /** @var ProviderMetadata $providerMetadata */
        $providerMetadata = $providerMethod->invoke(null);

        $this->expectException(\WordPress\AiClient\Common\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported model capabilities');

        $method->invoke(null, $modelMetadata, $providerMetadata);
    }

    public function testReadStringConfigValueReturnsNullForUndefinedConstant(): void
    {
        $method = new ReflectionMethod(OllamaProvider::class, 'readStringConfigValue');
        $method->setAccessible(true);

        $this->assertNull($method->invoke(null, 'TOTALLY_UNDEFINED_CONSTANT_XYZ'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReadStringConfigValueReturnsStringForDefinedScalar(): void
    {
        define('OLLAMA_TEST_CONFIG_VALUE', 42);

        $method = new ReflectionMethod(OllamaProvider::class, 'readStringConfigValue');
        $method->setAccessible(true);

        $this->assertSame('42', $method->invoke(null, 'OLLAMA_TEST_CONFIG_VALUE'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUrlCombinesBaseUrlAndPath(): void
    {
        define('OLLAMA_BASE_URL', 'http://my-ollama:8080/v1');

        $this->assertSame(
            'http://my-ollama:8080/v1/chat/completions',
            OllamaProvider::url('/chat/completions')
        );
    }
}
