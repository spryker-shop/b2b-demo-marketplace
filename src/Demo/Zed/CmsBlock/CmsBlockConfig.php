<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlock;

use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Zed\CmsBlock\CmsBlockConfig as SprykerCmsBlockConfig;

class CmsBlockConfig extends SprykerCmsBlockConfig
{
    public function getCmsBlockTemplatePaths(): array
    {
        $templatePaths = [];

        foreach ($this->getProjectNamespaces() as $projectNamespace) {
            $templatePaths[] = sprintf(
                '%s/%s/Shared/CmsBlock/Theme/%s',
                APPLICATION_SOURCE_DIR,
                $projectNamespace,
                static::THEME_NAME_DEFAULT,
            );
        }

        return $templatePaths;
    }

    /**
     * @return array<string>
     */
    protected function getProjectNamespaces(): array
    {
        return $this->get(KernelConstants::PROJECT_NAMESPACES);
    }
}
