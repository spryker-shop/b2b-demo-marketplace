<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductAttachmentStorage;

use Pyz\Zed\Synchronization\SynchronizationConfig;
use Spryker\Zed\ProductAttachmentStorage\ProductAttachmentStorageConfig as SprykerProductAttachmentStorageConfig;

class ProductAttachmentStorageConfig extends SprykerProductAttachmentStorageConfig
{
    public function getProductAttachmentSynchronizationPoolName(): ?string
    {
        return SynchronizationConfig::DEFAULT_SYNCHRONIZATION_POOL_NAME;
    }
}
