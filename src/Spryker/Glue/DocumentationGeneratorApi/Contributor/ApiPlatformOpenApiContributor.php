<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\DocumentationGeneratorApi\Contributor;

use Psr\Log\LoggerInterface;
use Spryker\Glue\DocumentationGeneratorApi\DocumentationGeneratorApiConfig;
use Symfony\Component\Process\Exception\ExceptionInterface;

class ApiPlatformOpenApiContributor implements OpenApiContributorInterface
{
    protected const string ENV_GLUE_APPLICATION = 'GLUE_APPLICATION';

    public function __construct(
        protected readonly DocumentationGeneratorApiConfig $config,
        protected readonly SymfonyProcessFactory $processFactory,
        protected readonly LoggerInterface $logger,
    ) {
    }

    public function contribute(string $applicationName): ?string
    {
        if (!class_exists($this->config->getApiPlatformDetectionClass())) {
            return null;
        }

        $glueApplication = $this->config->getApiPlatformGlueApplication($applicationName);
        if ($glueApplication === null) {
            return null;
        }

        $process = $this->processFactory->createGlueExportProcess(
            $this->config->getGlueConsoleBinPath(),
            $this->config->getApiPlatformExportCommand(),
            APPLICATION_ROOT_DIR,
            [static::ENV_GLUE_APPLICATION => $glueApplication],
        );
        $process->setTimeout($this->config->getApiPlatformProcessTimeoutSeconds());

        try {
            $process->run();
        } catch (ExceptionInterface $exception) {
            $this->logger->warning('api:openapi:export failed to start', [
                'application' => $applicationName,
                'exception' => $exception,
            ]);

            return null;
        }

        if (!$process->isSuccessful()) {
            $this->logger->warning('api:openapi:export non-zero exit', [
                'application' => $applicationName,
                'exit_code' => $process->getExitCode(),
                'stderr' => $process->getErrorOutput(),
            ]);

            return null;
        }

        $yaml = trim($process->getOutput());
        if ($yaml === '') {
            $this->logger->warning('api:openapi:export returned no output', [
                'application' => $applicationName,
            ]);

            return null;
        }

        return $yaml;
    }
}
