<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\DocumentationGeneratorApi\Contributor;

use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Spryker\Glue\DocumentationGeneratorApi\Contributor\ApiPlatformOpenApiContributor;
use Spryker\Glue\DocumentationGeneratorApi\Contributor\SymfonyProcessFactory;
use Spryker\Glue\DocumentationGeneratorApi\DocumentationGeneratorApiConfig;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group DocumentationGeneratorApi
 * @group Contributor
 * @group ApiPlatformOpenApiContributorTest
 * Add your own group annotations below this line
 */
class ApiPlatformOpenApiContributorTest extends Unit
{
    protected const string NON_EXISTENT_CLASS = 'NonExistent\\ApiPlatform\\Bundle\\NeverLoadedClass';

    protected const string APPLICATION_BACKEND = 'backend';

    protected const string GLUE_APPLICATION_BACKEND = 'GLUE_BACKEND';

    protected const int TIMEOUT_SECONDS = 120;

    public function testGivenApiPlatformIsNotInstalledWhenContributeIsCalledThenNullIsReturnedWithoutExport(): void
    {
        // Arrange
        $processFactory = $this->createMock(SymfonyProcessFactory::class);
        $processFactory->expects($this->never())->method('createGlueExportProcess');
        $contributor = new ApiPlatformOpenApiContributor(
            $this->createConfigMock(static::NON_EXISTENT_CLASS, static::GLUE_APPLICATION_BACKEND),
            $processFactory,
            $this->createMock(LoggerInterface::class),
        );

        // Act
        $contribution = $contributor->contribute(static::APPLICATION_BACKEND);

        // Assert
        $this->assertNull($contribution);
    }

    public function testGivenApplicationWithoutGlueApplicationWhenContributeIsCalledThenNullIsReturnedWithoutExport(): void
    {
        // Arrange
        $processFactory = $this->createMock(SymfonyProcessFactory::class);
        $processFactory->expects($this->never())->method('createGlueExportProcess');
        $contributor = new ApiPlatformOpenApiContributor(
            $this->createConfigMock(static::class, null),
            $processFactory,
            $this->createMock(LoggerInterface::class),
        );

        // Act
        $contribution = $contributor->contribute('unmapped');

        // Assert
        $this->assertNull($contribution);
    }

    public function testGivenSuccessfulExportWhenContributeIsCalledThenExportRunsUnderTheGlueApplicationAndTrimmedOutputIsReturned(): void
    {
        // Arrange
        $expectedYaml = "openapi: 3.0.0\npaths: {}";
        $process = $this->createProcessMock(true, $expectedYaml . "\n\n", 0, '');
        $process->expects($this->once())->method('setTimeout')->with(static::TIMEOUT_SECONDS);
        $processFactory = $this->createMock(SymfonyProcessFactory::class);
        $processFactory->expects($this->once())
            ->method('createGlueExportProcess')
            ->with($this->anything(), $this->anything(), $this->anything(), ['GLUE_APPLICATION' => static::GLUE_APPLICATION_BACKEND])
            ->willReturn($process);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $contributor = new ApiPlatformOpenApiContributor(
            $this->createConfigMock(static::class, static::GLUE_APPLICATION_BACKEND),
            $processFactory,
            $logger,
        );

        // Act
        $contribution = $contributor->contribute(static::APPLICATION_BACKEND);

        // Assert
        $this->assertSame($expectedYaml, $contribution);
    }

    public function testGivenExportExitsNonZeroWhenContributeIsCalledThenNullIsReturnedAndWarningIsLogged(): void
    {
        // Arrange
        $processFactory = $this->createMock(SymfonyProcessFactory::class);
        $processFactory->method('createGlueExportProcess')->willReturn($this->createProcessMock(false, '', 1, 'boom'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('api:openapi:export non-zero exit', $this->callback(static function (array $context): bool {
                return $context['application'] === static::APPLICATION_BACKEND
                    && $context['exit_code'] === 1
                    && $context['stderr'] === 'boom';
            }));
        $contributor = new ApiPlatformOpenApiContributor(
            $this->createConfigMock(static::class, static::GLUE_APPLICATION_BACKEND),
            $processFactory,
            $logger,
        );

        // Act
        $contribution = $contributor->contribute(static::APPLICATION_BACKEND);

        // Assert
        $this->assertNull($contribution);
    }

    public function testGivenExportFailsToStartWhenContributeIsCalledThenNullIsReturnedAndWarningIsLogged(): void
    {
        // Arrange
        $process = $this->createMock(Process::class);
        $process->method('run')->willThrowException(new RuntimeException('binary missing'));
        $processFactory = $this->createMock(SymfonyProcessFactory::class);
        $processFactory->method('createGlueExportProcess')->willReturn($process);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('api:openapi:export failed to start', $this->callback(static function (array $context): bool {
                return $context['exception'] instanceof RuntimeException;
            }));
        $contributor = new ApiPlatformOpenApiContributor(
            $this->createConfigMock(static::class, static::GLUE_APPLICATION_BACKEND),
            $processFactory,
            $logger,
        );

        // Act
        $contribution = $contributor->contribute(static::APPLICATION_BACKEND);

        // Assert
        $this->assertNull($contribution);
    }

    public function testGivenExportPrintsOnlyWhitespaceWhenContributeIsCalledThenNullIsReturned(): void
    {
        // Arrange
        $processFactory = $this->createMock(SymfonyProcessFactory::class);
        $processFactory->method('createGlueExportProcess')->willReturn($this->createProcessMock(true, "   \n", 0, ''));
        $contributor = new ApiPlatformOpenApiContributor(
            $this->createConfigMock(static::class, static::GLUE_APPLICATION_BACKEND),
            $processFactory,
            $this->createMock(LoggerInterface::class),
        );

        // Act
        $contribution = $contributor->contribute(static::APPLICATION_BACKEND);

        // Assert
        $this->assertNull($contribution);
    }

    protected function createConfigMock(string $detectionClass, ?string $glueApplication): DocumentationGeneratorApiConfig
    {
        $config = $this->createMock(DocumentationGeneratorApiConfig::class);
        $config->method('getApiPlatformDetectionClass')->willReturn($detectionClass);
        $config->method('getApiPlatformGlueApplication')->willReturn($glueApplication);
        $config->method('getGlueConsoleBinPath')->willReturn('/glue');
        $config->method('getApiPlatformExportCommand')->willReturn('api:openapi:export -y');
        $config->method('getApiPlatformProcessTimeoutSeconds')->willReturn(static::TIMEOUT_SECONDS);

        return $config;
    }

    protected function createProcessMock(bool $successful, string $output, int $exitCode, string $errorOutput): Process
    {
        $process = $this->createMock(Process::class);
        $process->method('run')->willReturn($exitCode);
        $process->method('isSuccessful')->willReturn($successful);
        $process->method('getOutput')->willReturn($output);
        $process->method('getExitCode')->willReturn($exitCode);
        $process->method('getErrorOutput')->willReturn($errorOutput);

        return $process;
    }
}
