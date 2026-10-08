<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\DocumentationGeneratorApi\Contributor;

use Symfony\Component\Process\Process;

class SymfonyProcessFactory
{
    /**
     * @param array<string, string> $env
     */
    public function createGlueExportProcess(string $binPath, string $command, string $cwd, array $env): Process
    {
        return Process::fromShellCommandline(
            sprintf('php %s %s', escapeshellarg($binPath), $command),
            $cwd,
            $env,
        );
    }
}
