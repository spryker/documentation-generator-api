<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\DocumentationGeneratorApi\Dependency\Client;

interface DocumentationGeneratorApiToStorageClientInterface
{
    public function set(string $key, string $value, ?int $ttl = null): void;
}
