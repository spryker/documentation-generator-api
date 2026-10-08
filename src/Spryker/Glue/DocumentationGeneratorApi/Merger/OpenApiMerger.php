<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\DocumentationGeneratorApi\Merger;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\OpenApi;
use InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class OpenApiMerger implements OpenApiMergerInterface
{
    protected const string REF_PREFIX = 'ApiPlatform_';

    protected const string REF_PATTERN_SCHEMA = '#/components/schemas/';

    protected const string KEY_PATHS = 'paths';

    protected const string KEY_COMPONENTS = 'components';

    protected const string KEY_SCHEMAS = 'schemas';

    protected const string KEY_SECURITY_SCHEMES = 'securitySchemes';

    protected const string KEY_PARAMETERS = 'parameters';

    protected const string KEY_TAGS = 'tags';

    protected const string KEY_SERVERS = 'servers';

    protected const string KEY_NAME = 'name';

    protected const string KEY_URL = 'url';

    protected const string REF_KEY = '$ref';

    protected const string SOURCE_LABEL_BASE = 'Base';

    protected const string SOURCE_LABEL_CONTRIBUTOR = 'Contributor';

    /**
     * @param array<string> $contributorYamls
     *
     * @throws \InvalidArgumentException
     */
    public function mergeYaml(string $baseYaml, array $contributorYamls): OpenApi
    {
        $merged = $this->parseYaml($baseYaml, static::SOURCE_LABEL_BASE);

        foreach ($contributorYamls as $contributorYaml) {
            $contributor = $this->parseYaml($contributorYaml, static::SOURCE_LABEL_CONTRIBUTOR);
            $contributor = $this->resolveSchemaCollisions($merged, $contributor);

            $merged = $this->mergePaths($merged, $contributor);
            $merged = $this->mergeComponents($merged, $contributor);
            $merged = $this->mergeTags($merged, $contributor);
            $merged = $this->mergeServers($merged, $contributor);
        }

        try {
            return new OpenApi($merged);
        } catch (TypeErrorException $exception) {
            throw new InvalidArgumentException(
                'Merged OpenAPI document is not structurally valid: ' . $exception->getMessage(),
                0,
                $exception,
            );
        }
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return array<string, mixed>
     */
    protected function parseYaml(string $yaml, string $sourceLabel): array
    {
        try {
            $data = Yaml::parse($yaml);
        } catch (ParseException $exception) {
            throw new InvalidArgumentException(
                sprintf('%s OpenAPI YAML is not valid YAML.', $sourceLabel),
                0,
                $exception,
            );
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('%s OpenAPI YAML must decode to a mapping.', $sourceLabel));
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $contributor
     *
     * @return array<string, mixed>
     */
    protected function resolveSchemaCollisions(array $base, array $contributor): array
    {
        $baseSchemas = $base[static::KEY_COMPONENTS][static::KEY_SCHEMAS] ?? [];
        $contributorSchemas = $contributor[static::KEY_COMPONENTS][static::KEY_SCHEMAS] ?? [];

        if ($baseSchemas === [] || $contributorSchemas === []) {
            return $contributor;
        }

        $rename = [];
        $dropAsDuplicate = [];

        foreach ($contributorSchemas as $name => $schema) {
            if (!array_key_exists($name, $baseSchemas)) {
                continue;
            }

            if ($baseSchemas[$name] === $schema) {
                $dropAsDuplicate[] = $name;

                continue;
            }

            $rename[$name] = static::REF_PREFIX . $name;
        }

        foreach ($dropAsDuplicate as $name) {
            unset($contributorSchemas[$name]);
        }

        if ($rename !== []) {
            foreach ($rename as $original => $renamed) {
                $contributorSchemas[$renamed] = $contributorSchemas[$original];
                unset($contributorSchemas[$original]);
            }

            $contributor = $this->rewriteSchemaRefs($contributor, $rename);
        }

        $contributor[static::KEY_COMPONENTS][static::KEY_SCHEMAS] = $contributorSchemas;

        return $contributor;
    }

    /**
     * @param array<string, mixed> $contributor
     * @param array<string, string> $rename
     *
     * @return array<string, mixed>
     */
    protected function rewriteSchemaRefs(array $contributor, array $rename): array
    {
        $walker = function (mixed &$value) use (&$walker, $rename): void {
            if (!is_array($value)) {
                return;
            }

            $name = $this->findSchemaName($value[static::REF_KEY] ?? null);
            if ($name !== null && isset($rename[$name])) {
                $value[static::REF_KEY] = static::REF_PATTERN_SCHEMA . $rename[$name];
            }

            foreach ($value as &$child) {
                $walker($child);
            }
            unset($child);
        };

        $walker($contributor);

        return $contributor;
    }

    protected function findSchemaName(mixed $ref): ?string
    {
        if (!is_string($ref) || !str_starts_with($ref, static::REF_PATTERN_SCHEMA)) {
            return null;
        }

        return substr($ref, strlen(static::REF_PATTERN_SCHEMA));
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $contributor
     *
     * @return array<string, mixed>
     */
    protected function mergePaths(array $base, array $contributor): array
    {
        $paths = ($base[static::KEY_PATHS] ?? []) + ($contributor[static::KEY_PATHS] ?? []);
        ksort($paths);
        $base[static::KEY_PATHS] = $paths;

        return $base;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $contributor
     *
     * @return array<string, mixed>
     */
    protected function mergeComponents(array $base, array $contributor): array
    {
        foreach ([static::KEY_SCHEMAS, static::KEY_SECURITY_SCHEMES, static::KEY_PARAMETERS] as $section) {
            $base[static::KEY_COMPONENTS][$section] =
                ($base[static::KEY_COMPONENTS][$section] ?? [])
                + ($contributor[static::KEY_COMPONENTS][$section] ?? []);
        }

        return $base;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $contributor
     *
     * @return array<string, mixed>
     */
    protected function mergeTags(array $base, array $contributor): array
    {
        $byName = [];
        foreach (($base[static::KEY_TAGS] ?? []) as $tag) {
            if (isset($tag[static::KEY_NAME])) {
                $byName[$tag[static::KEY_NAME]] = $tag;
            }
        }

        foreach (($contributor[static::KEY_TAGS] ?? []) as $tag) {
            if (!isset($tag[static::KEY_NAME]) || isset($byName[$tag[static::KEY_NAME]])) {
                continue;
            }
            $byName[$tag[static::KEY_NAME]] = $tag;
        }

        ksort($byName);
        $base[static::KEY_TAGS] = array_values($byName);

        return $base;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $contributor
     *
     * @return array<string, mixed>
     */
    protected function mergeServers(array $base, array $contributor): array
    {
        $byUrl = [];
        foreach (($base[static::KEY_SERVERS] ?? []) as $server) {
            if (isset($server[static::KEY_URL])) {
                $byUrl[$server[static::KEY_URL]] = $server;
            }
        }

        foreach (($contributor[static::KEY_SERVERS] ?? []) as $server) {
            if (!isset($server[static::KEY_URL]) || isset($byUrl[$server[static::KEY_URL]])) {
                continue;
            }
            $url = $server[static::KEY_URL];
            // API Platform emits a relative `/` server, which would resolve against the documentation host instead of the API host.
            if ($url === '' || str_ends_with($url, '/')) {
                continue;
            }
            $byUrl[$url] = $server;
        }

        $base[static::KEY_SERVERS] = array_values($byUrl);

        return $base;
    }
}
