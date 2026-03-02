<?php

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider\Tests\Unit\Metadata;

use ReflectionMethod;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Zaherg\OllamaAiProvider\Metadata\OllamaModelMetadataDirectory;

class OllamaModelMetadataDirectoryTest extends TestCase
{
    /**
     * @var OllamaModelMetadataDirectory
     */
    private $directory;

    /**
     * @var ReflectionMethod
     */
    private $parseMethod;

    /**
     * @var ReflectionMethod
     */
    private $isEmbeddingMethod;

    /**
     * @var ReflectionMethod
     */
    private $sortMethod;

    protected function set_up(): void
    {
        parent::set_up();
        $this->directory = new OllamaModelMetadataDirectory();

        $dirClass = OllamaModelMetadataDirectory::class;

        $this->parseMethod = new ReflectionMethod($dirClass, 'parseResponseToModelMetadataList');
        $this->parseMethod->setAccessible(true);

        $this->isEmbeddingMethod = new ReflectionMethod($dirClass, 'isLikelyEmbeddingModel');
        $this->isEmbeddingMethod->setAccessible(true);

        $this->sortMethod = new ReflectionMethod($dirClass, 'modelSortCallback');
        $this->sortMethod->setAccessible(true);
    }

    /**
     * Helper to create a Response mock with given data.
     *
     * @param mixed $data
     * @return Response
     */
    private function createResponse($data): Response
    {
        $response = $this->createMock(Response::class);
        $response->method('getData')->willReturn($data);

        return $response;
    }

    public function testParseResponseWithMultipleModelsReturnsCorrectList(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 'llama3:latest'],
                ['id' => 'codellama:7b'],
                ['id' => 'mistral:latest'],
            ],
        ]);

        /** @var ModelMetadata[] $models */
        $models = $this->parseMethod->invoke($this->directory, $response);

        $this->assertCount(3, $models);
        $this->assertContainsOnlyInstancesOf(ModelMetadata::class, $models);

        $ids = array_map(static function (ModelMetadata $m) {
            return $m->getId();
        }, $models);
        $this->assertContains('llama3:latest', $ids);
        $this->assertContains('codellama:7b', $ids);
        $this->assertContains('mistral:latest', $ids);
    }

    public function testParseResponseFiltersOutEmbeddingModels(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 'llama3:latest'],
                ['id' => 'nomic-embed-text:latest'],
                ['id' => 'mxbai-embed-large:latest'],
                ['id' => 'mistral:latest'],
            ],
        ]);

        /** @var ModelMetadata[] $models */
        $models = $this->parseMethod->invoke($this->directory, $response);

        $ids = array_map(static function (ModelMetadata $m) {
            return $m->getId();
        }, $models);

        $this->assertCount(2, $models);
        $this->assertContains('llama3:latest', $ids);
        $this->assertContains('mistral:latest', $ids);
        $this->assertNotContains('nomic-embed-text:latest', $ids);
        $this->assertNotContains('mxbai-embed-large:latest', $ids);
    }

    public function testParseResponseSortsLatestModelsFirst(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 'codellama:7b'],
                ['id' => 'llama3:latest'],
                ['id' => 'aya:8b'],
                ['id' => 'mistral:latest'],
            ],
        ]);

        /** @var ModelMetadata[] $models */
        $models = $this->parseMethod->invoke($this->directory, $response);

        $ids = array_map(static function (ModelMetadata $m) {
            return $m->getId();
        }, $models);

        // :latest models should come first, then alphabetical
        $this->assertSame('llama3:latest', $ids[0]);
        $this->assertSame('mistral:latest', $ids[1]);
        // Non-latest sorted alphabetically
        $this->assertSame('aya:8b', $ids[2]);
        $this->assertSame('codellama:7b', $ids[3]);
    }

    public function testParseResponseThrowsOnMissingDataKey(): void
    {
        $response = $this->createResponse(['models' => []]);

        $this->expectException(ResponseException::class);

        $this->parseMethod->invoke($this->directory, $response);
    }

    public function testParseResponseThrowsOnInvalidModelId(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 123],
            ],
        ]);

        $this->expectException(ResponseException::class);

        $this->parseMethod->invoke($this->directory, $response);
    }

    public function testParseResponseThrowsWhenAllModelsAreEmbeddings(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 'nomic-embed-text:latest'],
                ['id' => 'mxbai-embed-large:latest'],
            ],
        ]);

        $this->expectException(ResponseException::class);

        $this->parseMethod->invoke($this->directory, $response);
    }

    public function testIsLikelyEmbeddingModelReturnsTrueForEmbeddingModels(): void
    {
        $this->assertTrue($this->isEmbeddingMethod->invoke($this->directory, 'nomic-embed-text'));
        $this->assertTrue($this->isEmbeddingMethod->invoke($this->directory, 'mxbai-embed-large'));
        $this->assertTrue($this->isEmbeddingMethod->invoke($this->directory, 'NOMIC-EMBED-TEXT'));
    }

    public function testIsLikelyEmbeddingModelReturnsFalseForChatModels(): void
    {
        $this->assertFalse($this->isEmbeddingMethod->invoke($this->directory, 'llama3'));
        $this->assertFalse($this->isEmbeddingMethod->invoke($this->directory, 'mistral:latest'));
        $this->assertFalse($this->isEmbeddingMethod->invoke($this->directory, 'codellama:7b'));
    }

    public function testModelSortCallbackLatestTagWinsThenAlphabetical(): void
    {
        $latest = new ModelMetadata('llama3:latest', 'llama3:latest', [], []);
        $nonLatest = new ModelMetadata('codellama:7b', 'codellama:7b', [], []);
        $anotherLatest = new ModelMetadata('mistral:latest', 'mistral:latest', [], []);

        // latest vs non-latest: latest wins
        $this->assertLessThan(0, $this->sortMethod->invoke($this->directory, $latest, $nonLatest));
        $this->assertGreaterThan(0, $this->sortMethod->invoke($this->directory, $nonLatest, $latest));

        // both latest: alphabetical
        $this->assertLessThan(0, $this->sortMethod->invoke($this->directory, $latest, $anotherLatest));
        $this->assertGreaterThan(0, $this->sortMethod->invoke($this->directory, $anotherLatest, $latest));

        // both non-latest: alphabetical
        $anotherNonLatest = new ModelMetadata('aya:8b', 'aya:8b', [], []);
        $this->assertGreaterThan(0, $this->sortMethod->invoke($this->directory, $nonLatest, $anotherNonLatest));
        $this->assertLessThan(0, $this->sortMethod->invoke($this->directory, $anotherNonLatest, $nonLatest));

        // same model: zero
        $this->assertSame(0, $this->sortMethod->invoke($this->directory, $latest, $latest));
    }
}
