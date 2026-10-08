<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\DocumentationGeneratorApi\Merger;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\Writer;
use Codeception\Test\Unit;
use InvalidArgumentException;
use Spryker\Glue\DocumentationGeneratorApi\Merger\OpenApiMerger;
use Symfony\Component\Yaml\Yaml;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group DocumentationGeneratorApi
 * @group Merger
 * @group OpenApiMergerTest
 * Add your own group annotations below this line
 */
class OpenApiMergerTest extends Unit
{
    protected const string FIXTURE_DIR = __DIR__ . '/../../../../_data/SwaggerMerge';

    public function testGivenNoContributorWhenMergeYamlIsCalledThenBaseDocumentIsReturnedUnchanged(): void
    {
        // Arrange
        $baseYaml = file_get_contents(static::FIXTURE_DIR . '/base.yaml');

        // Act
        $merged = (new OpenApiMerger())->mergeYaml($baseYaml, []);

        // Assert
        $this->assertInstanceOf(OpenApi::class, $merged);
        $this->assertEquals(Yaml::parse($baseYaml), $this->toArray($merged));
    }

    public function testGivenContributorWhenMergeYamlIsCalledThenPathsTagsServersAndComponentsAreUnioned(): void
    {
        // Arrange
        $contributorYaml = file_get_contents(static::FIXTURE_DIR . '/api-platform.yaml');

        // Act
        $data = $this->toArray((new OpenApiMerger())->mergeYaml(file_get_contents(static::FIXTURE_DIR . '/base.yaml'), [$contributorYaml]));

        // Assert
        $this->assertArrayHasKey('/base-resource', $data['paths']);
        $this->assertArrayHasKey('/api-platform-resource', $data['paths']);
        $this->assertContains('Base', array_column($data['tags'], 'name'));
        $this->assertContains('ApiPlatform', array_column($data['tags'], 'name'));
        $this->assertContains('http://base.local', array_column($data['servers'], 'url'));
        $this->assertContains('http://api-platform.local', array_column($data['servers'], 'url'));
        $this->assertArrayHasKey('BaseOnly', $data['components']['schemas']);
        $this->assertArrayHasKey('ApiPlatformOnly', $data['components']['schemas']);
        $this->assertArrayHasKey('BaseAuth', $data['components']['securitySchemes']);
        $this->assertArrayHasKey('ApiPlatformAuth', $data['components']['securitySchemes']);
    }

    public function testGivenSchemaNameCollisionsWhenMergeYamlIsCalledThenIdenticalAreDedupedAndDifferingArePrefixedWithRefsRewritten(): void
    {
        // Arrange
        $contributorYaml = file_get_contents(static::FIXTURE_DIR . '/api-platform.yaml');

        // Act
        $data = $this->toArray((new OpenApiMerger())->mergeYaml(file_get_contents(static::FIXTURE_DIR . '/base.yaml'), [$contributorYaml]));

        // Assert
        $schemas = $data['components']['schemas'];
        $this->assertArrayHasKey('SharedIdentical', $schemas);
        $this->assertArrayNotHasKey('ApiPlatform_SharedIdentical', $schemas);
        $this->assertSame(['base_only_field'], array_keys($schemas['SharedDifferent']['properties']));
        $this->assertSame(['api_platform_only_field'], array_keys($schemas['ApiPlatform_SharedDifferent']['properties']));
        $this->assertSame(
            '#/components/schemas/ApiPlatform_SharedDifferent',
            $data['paths']['/api-platform-resource']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
    }

    public function testGivenContributorWhenMergeYamlIsCalledThenInfoComesFromBaseDocument(): void
    {
        // Arrange
        $contributorYaml = file_get_contents(static::FIXTURE_DIR . '/api-platform.yaml');

        // Act
        $data = $this->toArray((new OpenApiMerger())->mergeYaml(file_get_contents(static::FIXTURE_DIR . '/base.yaml'), [$contributorYaml]));

        // Assert
        $this->assertSame('Base API', $data['info']['title']);
        $this->assertSame('1.0.0', $data['info']['version']);
        $this->assertSame('Spryker', $data['info']['contact']['name']);
    }

    public function testGivenInvalidContributorYamlWhenMergeYamlIsCalledThenInvalidArgumentExceptionIsThrown(): void
    {
        // Arrange
        $merger = new OpenApiMerger();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $merger->mergeYaml(file_get_contents(static::FIXTURE_DIR . '/base.yaml'), ["openapi: 3.0.0\npaths:\n  - this: is: not: valid"]);
    }

    public function testGivenBaseYamlThatIsNotAMappingWhenMergeYamlIsCalledThenInvalidArgumentExceptionIsThrown(): void
    {
        // Arrange
        $merger = new OpenApiMerger();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $merger->mergeYaml('not-a-mapping', []);
    }

    public function testGivenMergedDocumentWhenWrittenToYamlThenBothSourcesArePresent(): void
    {
        // Arrange
        $contributorYaml = file_get_contents(static::FIXTURE_DIR . '/api-platform.yaml');
        $merged = (new OpenApiMerger())->mergeYaml(file_get_contents(static::FIXTURE_DIR . '/base.yaml'), [$contributorYaml]);

        // Act
        $yaml = Writer::writeToYaml($merged);

        // Assert
        $this->assertStringContainsString('/base-resource', $yaml);
        $this->assertStringContainsString('/api-platform-resource', $yaml);
        $this->assertStringContainsString('ApiPlatform_SharedDifferent', $yaml);
    }

    /**
     * @return array<string, mixed>
     */
    protected function toArray(OpenApi $openApi): array
    {
        return json_decode(json_encode($openApi->getSerializableData()), true);
    }
}
