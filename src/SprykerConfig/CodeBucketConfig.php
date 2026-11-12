<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerConfig;

use Spryker\Shared\Kernel\CodeBucket\Config\AbstractCodeBucketConfig;
use Symfony\Component\HttpFoundation\Request;

class CodeBucketConfig extends AbstractCodeBucketConfig
{
    /**
     * @return array<string>
     */
    public function getCodeBuckets(): array
    {
        return [
            'EU',
            'US',
            'DE',
            'AT',
        ];
    }

    /**
     * @deprecated This method implementation will be removed when environment configs are cleaned up.
     */
    public function getDefaultCodeBucket(): string
    {
        $codeBuckets = $this->getCodeBuckets();

        $parts = explode('/', $this->getPathInfo());
        if (isset($parts[1]) && in_array($parts[1], $codeBuckets, true)) {
            return $parts[1];
        }

        return defined('APPLICATION_REGION') ? APPLICATION_REGION : reset($codeBuckets);
    }

    protected function getPathInfo(): string
    {
        $requestFromGlobals = Request::createFromGlobals();

        return $requestFromGlobals->getPathInfo();
    }
}
