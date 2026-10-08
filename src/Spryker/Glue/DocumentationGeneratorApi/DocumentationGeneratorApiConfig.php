<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\DocumentationGeneratorApi;

use Spryker\Glue\Kernel\AbstractBundleConfig;

class DocumentationGeneratorApiConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    protected const API_SCHEMA_STORAGE_KEY_PATTERN = 'documentation:api:%s.schema.yml';

    /**
     * @uses \Spryker\Glue\GlueBackendApiApplication\Plugin\DocumentationGeneratorApi\BackendApiApplicationProviderPlugin::GLUE_BACKEND_API_APPLICATION
     */
    protected const string GLUE_BACKEND_API_APPLICATION = 'backend';

    /**
     * @var array<string, string>
     */
    protected const array API_PLATFORM_GLUE_APPLICATIONS = [
        self::GLUE_BACKEND_API_APPLICATION => 'GLUE_BACKEND',
    ];

    /**
     * Specification:
     * - Returns file path with generated documentation.
     *
     * @api
     *
     * @param string $applicationName
     *
     * @return string
     */
    public function getGeneratedFullFileName(string $applicationName): string
    {
        return sprintf(
            '%s/src/Generated/Glue%s/Specification/spryker_%s_api.schema.yml',
            APPLICATION_ROOT_DIR,
            ucfirst($applicationName),
            strtolower($applicationName),
        );
    }

    /**
     * Specification:
     * - Returns a Storage key pattern for the API schema.
     *
     * @api
     *
     * @return string
     */
    public function getApiSchemaStorageKeyPattern(): string
    {
        return static::API_SCHEMA_STORAGE_KEY_PATTERN;
    }

    /**
     * Specification:
     * - Returns the absolute path to the Glue console binary used to drive the
     *   API Platform OpenAPI exporter from the documentation generator.
     *
     * @api
     */
    public function getGlueConsoleBinPath(): string
    {
        return APPLICATION_ROOT_DIR . '/vendor/bin/glue';
    }

    /**
     * Specification:
     * - Returns the Glue console command (with flags) that emits the API Platform
     *   OpenAPI specification as YAML on stdout.
     * - Requests OpenAPI 3.0.0, the version of the generated specification the export
     *   is merged into, so nullable properties arrive as `nullable: true` instead of
     *   the `[type, null]` lists of OpenAPI 3.1 and later.
     *
     * @api
     */
    public function getApiPlatformExportCommand(): string
    {
        return 'api:openapi:export -y --spec-version=3.0.0 --quiet-meta';
    }

    /**
     * Specification:
     * - Maximum time in seconds the API Platform OpenAPI export subprocess may
     *   run before the contributor aborts and falls back to the generated spec alone.
     *
     * @api
     */
    public function getApiPlatformProcessTimeoutSeconds(): int
    {
        return 120;
    }

    /**
     * Specification:
     * - Class name (as a literal string, not `::class`) used to detect whether
     *   API Platform is installed in the current project. Returning a non-loaded
     *   class name here makes the contributor a no-op, preserving the generated spec.
     *
     * @api
     */
    public function getApiPlatformDetectionClass(): string
    {
        return 'ApiPlatform\\Symfony\\Bundle\\ApiPlatformBundle';
    }

    /**
     * Specification:
     * - Returns the `GLUE_APPLICATION` value the API Platform OpenAPI export runs under
     *   for the given API application, so the export holds only that application's resources.
     * - Returns `null` for an API application without API Platform resources, which skips the merge.
     *
     * @api
     */
    public function getApiPlatformGlueApplication(string $applicationName): ?string
    {
        return static::API_PLATFORM_GLUE_APPLICATIONS[$applicationName] ?? null;
    }
}
