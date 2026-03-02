<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Tests\Unit\Http;

use WordPress\AiClient\Common\Exception\RuntimeException as AiClientRuntimeException;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Zaherg\OllamaAiProvider\Http\NoOpRequestAuthentication;
use Zaherg\OllamaAiProvider\Http\OllamaHttpInitTrait;

/**
 * Base class that simulates the SDK parent behavior (always throws).
 *
 * The trait overrides getRequestAuthentication() and getHttpTransporter() and
 * calls parent:: internally, so this base gives us control.
 */
class FakeParentBase
{
    /**
     * @var bool
     */
    public $parentAuthShouldThrow = true;

    /**
     * @var RequestAuthenticationInterface|null
     */
    public $parentAuthResult;

    /**
     * @var bool
     */
    public $parentTransporterShouldThrow = true;

    /**
     * @var HttpTransporterInterface|null
     */
    private $httpTransporter;

    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        if ($this->parentAuthShouldThrow) {
            throw new AiClientRuntimeException('No auth configured');
        }

        /** @var RequestAuthenticationInterface $parentAuthResult */
        $parentAuthResult = $this->parentAuthResult;
        return $parentAuthResult;
    }

    public function getHttpTransporter(): HttpTransporterInterface
    {
        if ($this->parentTransporterShouldThrow && $this->httpTransporter === null) {
            throw new AiClientRuntimeException('No transporter configured');
        }

        if ($this->httpTransporter !== null) {
            return $this->httpTransporter;
        }

        throw new AiClientRuntimeException('No transporter configured');
    }

    public function setHttpTransporter(HttpTransporterInterface $transporter): void
    {
        $this->httpTransporter = $transporter;
        $this->parentTransporterShouldThrow = false;
    }
}

/**
 * Concrete test double that extends a fake parent and uses the trait.
 */
class TraitTestHost extends FakeParentBase
{
    use OllamaHttpInitTrait;
}

class OllamaHttpInitTraitTest extends TestCase
{
    public function testGetRequestAuthenticationFallsBackToNoOpWhenParentThrows(): void
    {
        $host = new TraitTestHost();
        $host->parentAuthShouldThrow = true;

        $auth = $host->getRequestAuthentication();

        $this->assertInstanceOf(NoOpRequestAuthentication::class, $auth);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetRequestAuthenticationFallsBackToApiKeyWhenKeySetAndParentThrows(): void
    {
        define('OLLAMA_API_KEY', 'sk-my-test-key');

        $host = new TraitTestHost();
        $host->parentAuthShouldThrow = true;

        $auth = $host->getRequestAuthentication();

        $this->assertInstanceOf(ApiKeyRequestAuthentication::class, $auth);
    }

    public function testGetRequestAuthenticationUsesParentWhenItDoesNotThrow(): void
    {
        $mockAuth = $this->createMock(RequestAuthenticationInterface::class);

        $host = new TraitTestHost();
        $host->parentAuthShouldThrow = false;
        $host->parentAuthResult = $mockAuth;

        $auth = $host->getRequestAuthentication();

        $this->assertSame($mockAuth, $auth);
    }

    public function testGetHttpTransporterUsesParentWhenItDoesNotThrow(): void
    {
        $mockTransporter = $this->createMock(HttpTransporterInterface::class);

        $host = new TraitTestHost();
        $host->setHttpTransporter($mockTransporter);

        $transporter = $host->getHttpTransporter();

        $this->assertSame($mockTransporter, $transporter);
    }

    public function testGetHttpTransporterFallbackAttemptsFactoryCreation(): void
    {
        $host = new TraitTestHost();
        $host->parentTransporterShouldThrow = true;

        // The trait catches AiClientRuntimeException from parent and calls
        // HttpTransporterFactory::createTransporter(). Without a PSR-18 client
        // installed, the factory throws a NotFoundException from php-http/discovery.
        // This proves the fallback path was entered.
        $this->expectException(\Http\Discovery\Exception\NotFoundException::class);

        $host->getHttpTransporter();
    }
}
