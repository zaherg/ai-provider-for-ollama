<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Tests\Unit\Http;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Zaherg\OllamaAiProvider\Http\NoOpRequestAuthentication;

class NoOpRequestAuthenticationTest extends TestCase
{
    /**
     * @var NoOpRequestAuthentication
     */
    private $auth;

    protected function set_up(): void
    {
        parent::set_up();
        $this->auth = new NoOpRequestAuthentication();
    }

    public function testAuthenticateRequestReturnsSameRequestUnchanged(): void
    {
        $request = new Request(
            HttpMethodEnum::POST(),
            'http://localhost:11434/v1/chat/completions',
            ['Content-Type' => 'application/json'],
            ['model' => 'llama3']
        );

        $result = $this->auth->authenticateRequest($request);

        $this->assertSame($request, $result);
    }

    public function testGetJsonSchemaReturnsExpectedEmptyObjectSchema(): void
    {
        $schema = NoOpRequestAuthentication::getJsonSchema();

        $this->assertSame([
            'type' => 'object',
            'properties' => [],
            'additionalProperties' => false,
        ], $schema);
    }
}
