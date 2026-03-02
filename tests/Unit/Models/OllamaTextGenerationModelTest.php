<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Tests\Unit\Models;

use ReflectionMethod;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Zaherg\OllamaAiProvider\Models\OllamaTextGenerationModel;
use Zaherg\OllamaAiProvider\Provider\OllamaProvider;

class OllamaTextGenerationModelTest extends TestCase
{
    /**
     * @var OllamaTextGenerationModel
     */
    private $model;

    protected function set_up(): void
    {
        parent::set_up();

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

        $this->model = new OllamaTextGenerationModel($modelMetadata, $providerMetadata);
    }

    public function testPrepareGenerateTextParamsSetsStreamToFalse(): void
    {
        $method = new ReflectionMethod(OllamaTextGenerationModel::class, 'prepareGenerateTextParams');
        $method->setAccessible(true);

        $message = new Message(MessageRoleEnum::user(), [new MessagePart('Hello')]);

        /** @var array<string, mixed> $params */
        $params = $method->invoke($this->model, [$message]);

        $this->assertIsArray($params);
        $this->assertArrayHasKey('stream', $params);
        $this->assertFalse($params['stream']);
    }

    public function testCreateRequestBuildsRequestWithCorrectUrl(): void
    {
        $method = new ReflectionMethod(OllamaTextGenerationModel::class, 'createRequest');
        $method->setAccessible(true);

        /** @var Request $request */
        $request = $method->invoke(
            $this->model,
            HttpMethodEnum::POST(),
            '/chat/completions',
            ['Content-Type' => 'application/json'],
            ['model' => 'llama3:latest']
        );

        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame(
            OllamaProvider::url('/chat/completions'),
            $request->getUri()
        );
    }
}
