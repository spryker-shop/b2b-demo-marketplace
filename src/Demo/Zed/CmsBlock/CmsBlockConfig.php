<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlock;

use Spryker\Zed\CmsBlock\CmsBlockConfig as SprykerCmsBlockConfig;

class CmsBlockConfig extends SprykerCmsBlockConfig
{
    public const string DEMO_NAMESPACE = 'Demo';

    public function getCmsBlockTemplatePaths(): array
    {
        return [
            ...parent::getCmsBlockTemplatePaths(),
            sprintf(
                '%s/%s/Shared/CmsBlock/Theme/%s',
                APPLICATION_SOURCE_DIR,
                static::DEMO_NAMESPACE,
                static::THEME_NAME_DEFAULT,
            ),
        ];
    }
}
