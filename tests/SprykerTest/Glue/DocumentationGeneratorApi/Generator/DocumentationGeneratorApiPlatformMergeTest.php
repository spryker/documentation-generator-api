<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\DocumentationGeneratorApi\Generator;

use cebe\openapi\spec\OpenApi;
use Codeception\Test\Unit;
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Spryker\Glue\DocumentationGeneratorApi\Contributor\OpenApiContributorInterface;
use Spryker\Glue\DocumentationGeneratorApi\Dependency\Client\DocumentationGeneratorApiToStorageClientInterface;
use Spryker\Glue\DocumentationGeneratorApi\Dependency\External\DocumentationGeneratorApiToFilesystemInterface;
use Spryker\Glue\DocumentationGeneratorApi\Dependency\Service\DocumentationGenerationApiToUtilEncodingServiceInterface;
use Spryker\Glue\DocumentationGeneratorApi\DocumentationGeneratorApiConfig;
use Spryker\Glue\DocumentationGeneratorApi\Expander\ContextExpanderCollectionInterface;
use Spryker\Glue\DocumentationGeneratorApi\Generator\DocumentationGenerator;
use Spryker\Glue\DocumentationGeneratorApi\Merger\OpenApiMergerInterface;
use Spryker\Glue\DocumentationGeneratorApiExtension\Dependency\Plugin\ApiApplicationProviderPluginInterface;
use Spryker\Glue\DocumentationGeneratorApiExtension\Dependency\Plugin\ContentGeneratorStrategyPluginInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group DocumentationGeneratorApi
 * @group Generator
 * @group DocumentationGeneratorApiPlatformMergeTest
 * Add your own group annotations below this line
 */
class DocumentationGeneratorApiPlatformMergeTest extends Unit
{
    protected const string APPLICATION_BACKEND = 'backend';

    protected const string BASE_CONTENT = 'base-content';

    protected const string CONTRIBUTION = 'openapi: 3.0.0';

    protected string $fileName;

    protected ?string $storedFileData = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileName = vfsStream::setup('root')->url() . '/spryker_backend_api.schema.yml';
    }

    public function testGivenNoContributionWhenGenerateDocumentationIsCalledThenGeneratedSpecIsWrittenAndStoredUnchanged(): void
    {
        // Arrange
        $merger = $this->createMock(OpenApiMergerInterface::class);
        $merger->expects($this->never())->method('mergeYaml');
        $generator = $this->createGenerator($this->createContributorMock(null), $merger, $this->createMock(LoggerInterface::class));

        // Act
        $generator->generateDocumentation();

        // Assert
        $this->assertSame(static::BASE_CONTENT, file_get_contents($this->fileName));
        $this->assertSame(static::BASE_CONTENT, $this->storedFileData);
    }

    public function testGivenContributionWhenGenerateDocumentationIsCalledThenMergedSpecIsWrittenAndStored(): void
    {
        // Arrange
        $merger = $this->createMock(OpenApiMergerInterface::class);
        $merger->expects($this->once())
            ->method('mergeYaml')
            ->with(static::BASE_CONTENT, [static::CONTRIBUTION])
            ->willReturn(new OpenApi([
                'openapi' => '3.0.0',
                'info' => ['title' => 'Merged', 'version' => '1.0.0'],
                'paths' => [
                    '/base' => ['get' => ['responses' => ['200' => ['description' => 'ok']]]],
                    '/api-platform' => ['get' => ['responses' => ['200' => ['description' => 'ok']]]],
                ],
            ]));
        $filesystem = $this->createFilesystemMock();
        $filesystem->expects($this->once())->method('dumpFile');
        $generator = $this->createGenerator($this->createContributorMock(static::CONTRIBUTION), $merger, $this->createMock(LoggerInterface::class), $filesystem);

        // Act
        $generator->generateDocumentation();

        // Assert
        $written = file_get_contents($this->fileName);
        $this->assertStringContainsString('/base', $written);
        $this->assertStringContainsString('/api-platform', $written);
        $this->assertSame($written, $this->storedFileData);
    }

    public function testGivenMergeFailureWhenGenerateDocumentationIsCalledThenGeneratedSpecIsKeptAndWarningIsLogged(): void
    {
        // Arrange
        $merger = $this->createMock(OpenApiMergerInterface::class);
        $merger->method('mergeYaml')->willThrowException(new InvalidArgumentException('boom'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Failed to merge API Platform OpenAPI into API application spec', $this->callback(static function (array $context): bool {
                return $context['application'] === static::APPLICATION_BACKEND && $context['exception'] instanceof InvalidArgumentException;
            }));
        $generator = $this->createGenerator($this->createContributorMock(static::CONTRIBUTION), $merger, $logger);

        // Act
        $generator->generateDocumentation();

        // Assert
        $this->assertSame(static::BASE_CONTENT, file_get_contents($this->fileName));
        $this->assertSame(static::BASE_CONTENT, $this->storedFileData);
    }

    protected function createGenerator(
        OpenApiContributorInterface $contributor,
        OpenApiMergerInterface $merger,
        LoggerInterface $logger,
        ?DocumentationGeneratorApiToFilesystemInterface $filesystem = null
    ): DocumentationGenerator {
        $apiApplicationProviderPlugin = $this->createMock(ApiApplicationProviderPluginInterface::class);
        $apiApplicationProviderPlugin->method('getName')->willReturn(static::APPLICATION_BACKEND);

        $contextExpanderCollection = $this->createMock(ContextExpanderCollectionInterface::class);
        $contextExpanderCollection->method('getExpanders')->willReturn([]);

        $config = $this->createMock(DocumentationGeneratorApiConfig::class);
        $config->method('getGeneratedFullFileName')->willReturn($this->fileName);
        $config->method('getApiSchemaStorageKeyPattern')->willReturn('documentation:api:%s.schema.yml');

        $contentGeneratorStrategyPlugin = $this->createMock(ContentGeneratorStrategyPluginInterface::class);
        $contentGeneratorStrategyPlugin->method('generateContent')->willReturn(static::BASE_CONTENT);

        $storageClient = $this->createMock(DocumentationGeneratorApiToStorageClientInterface::class);
        $storageClient->method('set')->willReturnCallback(function (string $key, string $value): void {
            $this->storedFileData = json_decode($value, true)['file_data'];
        });

        $utilEncodingService = $this->createMock(DocumentationGenerationApiToUtilEncodingServiceInterface::class);
        $utilEncodingService->method('encodeJson')->willReturnCallback(static fn (array $value): string => json_encode($value));

        return new DocumentationGenerator(
            [$apiApplicationProviderPlugin],
            $contextExpanderCollection,
            $filesystem ?? $this->createFilesystemMock(),
            $config,
            [],
            $contentGeneratorStrategyPlugin,
            $storageClient,
            $utilEncodingService,
            $contributor,
            $merger,
            $logger,
        );
    }

    protected function createFilesystemMock(): DocumentationGeneratorApiToFilesystemInterface&MockObject
    {
        $filesystem = $this->createMock(DocumentationGeneratorApiToFilesystemInterface::class);
        $filesystem->method('dumpFile')->willReturnCallback(static function (string $fileName, string $content): void {
            file_put_contents($fileName, $content);
        });

        return $filesystem;
    }

    protected function createContributorMock(?string $contribution): OpenApiContributorInterface
    {
        $contributor = $this->createMock(OpenApiContributorInterface::class);
        $contributor->method('contribute')->with(static::APPLICATION_BACKEND)->willReturn($contribution);

        return $contributor;
    }
}
